<?php ob_start() ?>
  <button type="button" class="btn btn-outline-secondary btn-sm" id="docker-refresh">
    <i class="fas fa-sync-alt me-1"></i><?php echo _("Refresh"); ?>
  </button>
<?php $buttons = ob_get_clean(); ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">

      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php echo _($__template_data['title']); ?>
          </div>
          <div class="col">
            <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
              <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $__template_data['serviceStatus']; ?>"></i></span>
              <span class="text service-status"><?php echo $__template_data['serviceName']; ?> <?php echo $__template_data['serviceStatus']; ?></span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>

          <!-- Nav tabs -->
          <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" id="docker-statustab" href="#docker-status" aria-controls="docker-status" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="docker-imagestab" href="#docker-images" aria-controls="docker-images" data-bs-toggle="tab"><?php echo _("Images"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="docker-containerstab" href="#docker-containers" aria-controls="docker-containers" data-bs-toggle="tab"><?php echo _("Containers"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="docker-composetab" href="#docker-compose" aria-controls="docker-compose" data-bs-toggle="tab"><?php echo _("Compose"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="docker-volumestab" href="#docker-volumes" aria-controls="docker-volumes" data-bs-toggle="tab"><?php echo _("Volumes"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="docker-abouttab" href="#docker-about" aria-controls="docker-about" data-bs-toggle="tab"><?php echo _("About"); ?></a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("tabs/status", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/images", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/containers", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/compose", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/volumes", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/about", $__template_data, $__template_data['pluginName']) ?>
          </div><!-- /.tab-content -->

          <?php echo $buttons ?>
        </form>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by docker"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<script src="app/js/plugins/Docker.js"></script>
