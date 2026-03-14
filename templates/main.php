<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline btn-primary" name="saveSettings" value="<?php echo _("Save settings"); ?>" />
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>
 
<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php echo htmlspecialchars($__template_data['title']); ?>
          </div>
          <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <div class="btn-group" role="group">
              <?php if (!RASPI_MONITOR_ENABLED) : ?>
                <?php if ($__template_data['serviceStatus'] == 'down') : ?>
                  <button type="submit" class="btn btn-sm btn-light" title="<?php echo _("Start Sample service"); ?>" name="startSampleService">
                    <i class="fas fa-play"></i>
                  </button>
                <?php else : ?>
                  <button type="submit" class="btn btn-sm btn-danger" title="<?php echo _("Stop Sample service"); ?>" name="stopSampleService">
                    <i class="fas fa-stop"></i>
                  </button>
                <?php endif; ?>
              <?php endif ?>
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $__template_data['serviceStatus']; ?>"></i></span>
                <span class="text service-status"><?php echo $__template_data['serviceName']; ?></span>
              </button>
            </div>
          </form>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST" class="needs-validation" novalidate>
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <!-- Nav tabs -->
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs">
              <li class="nav-item"><a class="nav-link active" id="samplesettingstab" href="#samplesettings" data-bs-toggle="tab"><?php echo _("Settings"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="samplestatustab" href="#samplestatus" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="sampleabouttab" href="#sampleabout" data-bs-toggle="tab"><?php echo _("About"); ?></a></li>
            </ul>
          </div>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("tabs/basic", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/status", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/about", $__template_data, $__template_data['pluginName']) ?>
          </div><!-- /.tab-content -->

          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
        </form>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by ". $__template_data['serviceName']); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

