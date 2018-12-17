<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules;
/*
* Class stub for BMO Module class
* In getActionbar change "modulename" to the display value for the page
* In getActionbar change extdisplay to align with whatever variable you use to decide if the page is in edit mode.
*
*/

class Contatta extends \FreePBX_Helpers implements \BMO
{

	// Note that the default Constructor comes from BMO/Self_Helper.
	// You may override it here if you wish. By default every BMO
	// object, when created, is handed the FreePBX Singleton object.

	// Do not use these functions to reference a function that may not
	// exist yet - for example, if you add 'testFunction', it may not
	// be visibile in here, as the PREVIOUS Class may already be loaded.
	//
	// Use install.php or uninstall.php instead, which guarantee a new
	// instance of this object.
	public function install()
	{
            if(!$this->getConfig('agiip1') && !$this->getConfig('amipassword')) {
                $this->setConfig('agiip1','');
                $this->setConfig('agiip2','');
                $this->setConfig('monitorexec','');
                $this->setConfig('ami','0');
                $this->setConfig('amipassword',$this->password());
            }
	}
	public function uninstall()
	{
		$this->FreePBX->Config->reset_conf_settings(array('TRANSFER_CONTEXT'),true);
	}

	// The following two stubs are planned for implementation in FreePBX 15.
	public function backup()
	{
	}
	public function restore($backup)
	{
	}

	// http://wiki.freepbx.org/display/FOP/BMO+Hooks#BMOHooks-HTTPHooks(ConfigPageInits)
	//
	// This handles any data passed to this module before the page is rendered.
	public function doConfigPageInit($page) {
		//Handle form submissions
		switch ($_REQUEST['action']) {
			case 'save':
			foreach (['agiip1','agiip2','monitorexec','ami','amipassword'] as $keyword) {
				$this->setConfig($keyword,$_REQUEST[$keyword]);
			}
                        $dbh = \FreePBX::Database();
                        $sql = 'DELETE IGNORE FROM `manager` WHERE `name`="contatta"';
                        $sth = $dbh->prepare($sql);
                        $sth->execute(array());
                        if ($_REQUEST['ami'] && isset($_REQUEST['amipassword']) && !empty($_REQUEST['amipassword'])) {
                            //Set ami password
                            $sql = 'INSERT INTO `manager` set name="contatta", secret=?, deny="0.0.0.0/0.0.0.0", permit="0.0.0.0/0.0.0.0", `read`="system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate" , `write`="system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate", `writetimeout`=100';
                            $sth = $dbh->prepare($sql);
                            $sth->execute(array($_REQUEST['amipassword']));
                        }
			needreload();
			break;
		}
	}

	// We want to do dialplan stuff.
	public static function myDialplanHooks()
	{
		return 900; //at the very last instance
	}

	public function doDialplanHook(&$ext, $engine, $priority)
	{
            $settings = $this->getAll();
            $agiip1 = !empty($settings['agiip1']) ? $settings['agiip1'] : '';
            $agiip2 = !empty($settings['agiip2']) ? $settings['agiip2'] : '';
            $monitorexec = !empty($settings['monitorexec']) ? $settings['monitorexec'] : '';
            $ami = !empty($settings['ami']) ? $settings['ami'] : '';
            $amipassword = !empty($settings['amipassword']) ? $settings['amipassword'] : '';

            if (!empty($monitorexec)) {
                $ext->addGlobal('MONITOR_EXEC', $monitorexec);
            }
            //[contatta]
            $context = 'contatta';

            //Configurazione Route Point
            $exten = '81XXX';
            $ext->add($context, $exten, '', new \ext_ringing());
            $ext->add($context, $exten, '', new \ext_agi('agi://'.$agiip1));
            $ext->add($context, $exten, '', new \ext_gotoif('$["${AGISTATUS}" = "FAILURE"]','altaaffidabilita'));
            $ext->add($context, $exten, 'altaaffidabilita', new \ext_agi('agi://'.$agiip2));

            //Configurazione Line IVR
            $exten = '82XXX';
            $ext->add($context, $exten, '', new \ext_agi('agi://'.$agiip1));
            $ext->add($context, $exten, '', new \ext_gotoif('$["${AGISTATUS}" = "FAILURE"]','altaaffidabilita'));
            $ext->add($context, $exten, 'altaaffidabilita', new \ext_agi('agi://'.$agiip2));

            //Stanza di conference
            $exten = '85000';
            $ext->add($context, $exten, '', new \ext_noop('conference'));
            $ext->add($context, $exten, '', new \ext_answer(''));
            $ext->add($context, $exten, '', new \ext_noop('amministratore exten: ${EXTEN}'));
            $ext->add($context, $exten, '', new \ext_gotoif('$[${EXTEN:-1}=1 || ${EXTEN:-1}=2]','proprietarioyes'));
            $ext->add($context, $exten, '', new \ext_goto('proprietariono'));
            $ext->add($context, $exten, 'proprietarioyes', new \ext_set('proprietario','yes'));
            $ext->add($context, $exten, '', new \ext_goto('proprietariosetted'));
            $ext->add($context, $exten, 'proprietariono', new \ext_set('proprietario','no'));
            $ext->add($context, $exten, 'proprietariosetted', new \ext_noop('amministratore proprietario: ${proprietario}'));
            $ext->add($context, $exten, '', new \ext_set('CONFBRIDGE(user,marked)','${proprietario}'));
            $ext->add($context, $exten, '', new \ext_set('CONFBRIDGE(user,end_marked)','yes'));
            $ext->add($context, $exten, '', new \ext_set('CONFBRIDGE(user,quiet)','yes'));
            $ext->add($context, $exten, '', new \ext_meetme('${EXTEN:0:-1}'));

            $context = 'webcall';
            $exten = '_89XXX.';
            $ext->add($context, $exten, '', new \ext_goto('contatta,81${EXTEN:-2},1'));

            $context = 'macro-contatta';
            $exten = 's';
            $ext->add($context, $exten, '', new \ext_agi('agi://${ARG2}/contatta_${ARG1}'));

            $context = 'makecall-contatta';
            $exten = 'failed';
            $ext->add($context, $exten, '', new \ext_agi('agi://'.$agiip1));
            $ext->add($context, $exten, '', new \ext_gotoif('$["${AGISTATUS}" = "FAILURE"]','altaaffidabilita'));
            $ext->add($context, $exten, '', new \ext_agi('agi://'.$agiip2));

            //;Se risposta ok
            //;non risposta non entra
            $exten = '_X!';
            $ext->add($context, $exten, '', new \ext_agi('agi://'.$agiip1));
            $ext->add($context, $exten, '', new \ext_gotoif('$["${AGISTATUS}" = "FAILURE"]','altaaffidabilita'));
            $ext->add($context, $exten, '', new \ext_agi('agi://'.$agiip2));
	}

	// http://wiki.freepbx.org/pages/viewpage.action?pageId=29753755
	public function getActionBar($request)
	{
		$buttons = array();
		switch ($request['display']) {
			case 'contatta':
			$buttons = array(
				'submit' => array(
					'name' => 'submit',
					'id' => 'submit',
					'value' => _('Submit')
				)
			);
			if (empty($request['extdisplay'])) {
				unset($buttons['delete']);
			}
			break;
		}
		return $buttons;
	}

	public function showPage()
	{
		$settings = $this->getAll();
		$subhead = _('Contatta');
		$content = load_view(__DIR__.'/views/form.php', array('settings' => $settings));
		show_view(__DIR__.'/views/default.php', array('subhead' => $subhead, 'content' => $content));
	}

        public function password($length = 15) {
            $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }
}
