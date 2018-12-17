<form action="" method="post" class="fpbx-submit" id="hwform" name="hwform" data-fpbx-delete="config.php?display=contatta">
  <input type="hidden" name='action' value="save">

  <!--AGI IP 1-->
  <div class="element-container">
    <div class="row">
      <div class="form-group">
        <div class="col-md-3">
          <label class="control-label" for="agiip1"><?php echo _("AGI IP 1") ?></label>
          <i class="fa fa-question-circle fpbx-help-icon" data-for="agiip1"></i>
        </div>
        <div class="col-md-9">
          <input type="text" class="form-control" id="agiip1" name="agiip1" value="<?php echo $settings['agiip1'];?>">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <span id="agiip1-help" class="help-block fpbx-help-block"><?php echo _("First AGI Server IP")?></span>
      </div>
    </div>
  </div>

  <div class="element-container">
    <div class="row">
      <div class="form-group">
        <div class="col-md-3">
          <label class="control-label" for="agiip2"><?php echo _("AGI IP 2") ?></label>
          <i class="fa fa-question-circle fpbx-help-icon" data-for="agiip2"></i>
        </div>
        <div class="col-md-9">
          <input type="text" class="form-control" id="agiip2" name="agiip2" value="<?php echo $settings['agiip2'];?>">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <span id="agiip2-help" class="help-block fpbx-help-block"><?php echo _("AGI Backup Server IP")?></span>
      </div>
    </div>
  </div>

  <div class="element-container">
    <div class="row">
      <div class="form-group">
        <div class="col-md-3">
          <label class="control-label" for="monitorexec"><?php echo _("Monitor Exec") ?></label>
          <i class="fa fa-question-circle fpbx-help-icon" data-for="monitorexec"></i>
        </div>
        <div class="col-md-9">
          <select class="form-control" id="monitorexec" name="monitorexec">
          <?php $monitors = array('monitor_1', 'monitor_2', 'monitor_3', 'monitor_4')?>
          <?php foreach($monitors as $monitor) {?>
              <option value="<?php echo $monitor?>" <?php echo ($monitor == $settings['monitorexec']) ? 'SELECTED': ''?>><?php echo $monitor?></option>
          <?php } ?>
          </select>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <span id="monitorexec-help" class="help-block fpbx-help-block"><?php echo _("Select MONITOR script")?></span>
      </div>
    </div>
  </div>

  <!--enabled-->
  <div class="element-container">
    <div class="row">
      <div class="form-group">
        <div class="col-md-3">
          <label class="control-label" for="ami"><?php echo _("Enable AMI user") ?></label>
          <i class="fa fa-question-circle fpbx-help-icon" data-for="ami"></i>
        </div>
        <div class="col-md-9 radioset">
          <input type="radio" name="ami" id="amiyes" value="1" <?php echo ($settings['ami'])?"CHECKED":""?>>
          <label for="amiyes"><?php echo _("Yes");?></label>
          <input type="radio" name="ami" id="amino" value="" <?php echo ($settings['ami'])?"":"CHECKED"?>>
          <label for="amino"><?php echo _("No");?></label>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <span id="ami-help" class="help-block fpbx-help-block"><?php echo _("Enable or disable AMI user for Polycom on-demand registration")?></span>
      </div>
    </div>
  </div>
  <!--enabled end-->
  <!--AMI password-->
  <div class="element-container">
    <div class="row">
      <div class="form-group">
        <div class="col-md-3">
          <label class="control-label" for="amipassword"><?php echo _("AMI Password") ?></label>
          <i class="fa fa-question-circle fpbx-help-icon" data-for="amipassword"></i>
        </div>
        <div class="col-md-9 radioset">
          <input type="text" class="form-control" id="amipassword" name="amipassword" value="<?php echo $settings['amipassword'];?>">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <span id="amipassword-help" class="help-block fpbx-help-block"><?php echo _("AMI password for Polycom on-demand registration")?></span>
      </div>
    </div>
  </div>
  <!--AMI password end-->


</form>
