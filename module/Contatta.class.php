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
            if(!$this->getConfig('agiip') && !$this->getConfig('amipassword')) {
                $this->setConfig('agiip','');
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
			foreach (['agiip','monitorexec','ami','amipassword'] as $keyword) {
				$this->setConfig($keyword,$_REQUEST[$keyword]);
			}
                        $dbh = \FreePBX::Database();
                        $sql = 'DELETE IGNORE FROM `manager` WHERE `name`="contatta"';
                        $sth = $dbh->prepare($sql);
                        $sth->execute(array());
                        if ($_REQUEST['ami'] && isset($_REQUEST['amipassword']) && !empty($_REQUEST['amipassword'])) {
                            //Set ami password
                            $sql = 'INSERT INTO `manager` set name="contatta", secret=?, deny="0.0.0.0/0.0.0.0", permit="0.0.0.0/0.0.0.0", `read`="system,call,originate" , `write`="system,call,originate", `writetimeout`=100';
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
            $agiip = !empty($settings['agiip']) ? $settings['agiip'] : '';
            $monitorexec = !empty($settings['monitorexec']) ? $settings['monitorexec'] : '';
            $ami = !empty($settings['ami']) ? $settings['ami'] : '';
            $amipassword = !empty($settings['amipassword']) ? $settings['amipassword'] : '';

            if (!empty($monitorexec)) {
                $ext->addGlobal('MONITOR_EXEC', $monitorexec);
            }
            //[contatta]
            $context = 'contatta';

            //Configurazione Route Point
            $exten = '_81.';
            $ext->add($context, $exten, '', new \ext_ringing());
            foreach (explode(',',$agiip) as $ip) {
                $ext->add($context, $exten, '', new \ext_agi('agi://'.$ip));
            }
            $ext->add($context, $exten, '', new \ext_hangup());

            //Configurazione Line IVR
            $exten = '_82.';
            $ext->add($context, $exten, '', new \ext_noop('match regex trasferimento cieco=${REGEX("contatta_blind_transfer=true",${SIPREFERTOHDR})}'));
            $ext->add($context, $exten, '', new \ext_gotoif('$["${REGEX("contatta_blind_transfer=true",${SIPREFERTOHDR})}" = "0"]','agi'));
            $ext->add($context, $exten, '', new \ext_answer(''));
            $ext->add($context, $exten, '', new \ext_noop('attendo 1 secondo'));
            $ext->add($context, $exten, '', new \ext_wait('1'));

            $ext->add($context, $exten, 'agi', new \ext_noop('eseguo agi waveline'));
            foreach (explode(',',$agiip) as $ip) {
                $ext->add($context, $exten, '', new \ext_agi('agi://'.$ip));
            }

            //Stanza di conference
            $exten = '_85000.';
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

            //Configurazione Linee intrusione
            $exten = '_85010.';
            foreach (explode(',',$agiip) as $ip) {
                $ext->add($context, $exten, '', new \ext_agi('agi://'.$ip));
            }
	    
            $exten = "_85020.";
            $ext->add($context, $exten, '', new \ext_noop('attivo registrazione'));
            $ext->add($context, $exten, '', new \ext_set('NUMERO_CHIAMATO','${EXTEN:5}'));
            $ext->add($context, $exten, '', new \ext_set('NAME_FILE','${PJSIP_HEADER(read,X-Elly-Rec)}'));
            $ext->add($context, $exten, '', new \ext_mixmonitor('','br(${NAME_FILE}_r.wav)t(${NAME_FILE}_t.wav)','${MONITOR_EXEC} ${NAME_FILE}_r.wav ${NAME_FILE}_t.wav ${NAME_FILE}'));
            $ext->add($context, $exten, '', new \ext_goto('1','${NUMERO_CHIAMATO}','from-internal'));

	    $context = 'webcall';
            $exten = '_89XXX';
            $ext->add($context, $exten, '', new \ext_goto('contatta,81${EXTEN:-3},1'));

            $context = 'macro-contatta';
            $exten = 's';

	    $ext->add($context, $exten, '', new \ext_gotoif('$["${fileWaveAgent}" = ""]','rec'));
	    $ext->add($context, $exten, '', new \ext_background('${fileWaveAgent}'));
            $ext->add($context, $exten, 'rec', new \ext_gotoif('$["${ARG3}" = ""]','agi'));
	    //mixmon
	    $ext->add($context, $exten, '', new \ext_mixmonitor('','br(${ARG3}_r.wav)t(${ARG3}_t.wav)','${MONITOR_EXEC} ${ARG3}_r.wav ${ARG3}_t.wav ${ARG3}'));
	    $ext->add($context, $exten, '', new \ext_set('MONITORED','true'));
            $ext->add($context, $exten, 'agi', new \ext_agi('agi://${ARG2}/contatta_${ARG1}/record_${MONITORED}'));

            $context = 'makecall-contatta';
			$exten = 'failed';

			$ext->add($context, $exten, '', new \ext_set('AGIESEGUITO','true'));
            foreach (explode(',',$agiip) as $ip) {
                $ext->add($context, $exten, '', new \ext_agi('agi://'.$ip.',${REASON}'));
            }

            //;Se risposta ok
            //;non risposta non entra
			$exten = '_X!';

			$ext->add($context, $exten, '', new \ext_gotoif('$["${AMD}" != "true"]','makecall'));

			$ext->add($context, $exten, '', new \ext_set('ESTENSIONE','${EXTEN}'));

			$ext->add($context, $exten, '', new \extension('WaitForNoise(300,1,3)'));
			$ext->add($context, $exten, '', new \ext_gotoif('$["${WAITSTATUS}" != "TIMEOUT"]','predictive'));
			$ext->add($context, $exten, '', new \ext_set('AMDSTATUS','HUMAN'));
			$ext->add($context, $exten, '', new \ext_goto('makecall'));
			$ext->add($context, $exten, 'predictive', new \extension('AMD()'));
			$ext->add($context, $exten, 'makecall', new \ext_set('AGIESEGUITO','true'));

            foreach (explode(',',$agiip) as $ip) {
                $ext->add($context, $exten, '', new \ext_agi('agi://'.$ip.',${AMDSTATUS}'));
			}

			$exten = 'h';

			$ext->add($context, $exten, '', new \ext_noop('estensione ${ESTENSIONE}'));
			$ext->add($context, $exten, '', new \ext_gotoif('$["${AGIESEGUITO}" == "true"]','fine'));
			$ext->add($context, $exten, '', new \ext_set('CALLERID(num)','${ESTENSIONE}'));

			foreach (explode(',',$agiip) as $ip) {
                $ext->add($context, $exten, '', new \ext_agi('agi://'.$ip.',HANGUP'));
			}

			$ext->add($context, $exten, 'fine', new \ext_noop('chiamata chiusa'));

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
