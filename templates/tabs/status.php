<!-- status tab -->
<div class="tab-pane fade show active" id="docker-status" role="tabpanel">

  <?php if ($__template_data['daemonStatus'] !== 'active'): ?>
  <div class="alert alert-warning mt-3" role="alert">
    <?php echo _("Docker daemon is not running."); ?>
    <button type="button" class="btn btn-sm btn-warning ms-2" id="js-docker-daemon-start"><?php echo _("Start Docker"); ?></button>
  </div>
  <?php endif; ?>

  <!-- Summary stats -->
  <div class="row mb-3 mt-3">
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body py-2">
          <div class="small text-muted"><?php echo _("Daemon"); ?></div>
          <?php if ($__template_data['daemonStatus'] === 'active'): ?>
            <span class="badge bg-success"><?php echo _("Active"); ?></span>
          <?php else: ?>
            <span class="badge bg-danger"><?php echo _("Inactive"); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body py-2">
          <div class="small text-muted"><?php echo _("Running"); ?></div>
          <strong>
            <?php
              $running = 0;
              foreach ($__template_data['containers'] as $c) {
                  if (($c->State ?? '') === 'running') {
                      $running++;
                  }
              }
              echo $running;
            ?>
          </strong>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body py-2">
          <div class="small text-muted"><?php echo _("Stopped"); ?></div>
          <strong>
            <?php
              $stopped = 0;
              foreach ($__template_data['containers'] as $c) {
                  if (($c->State ?? '') !== 'running') {
                      $stopped++;
                  }
              }
              echo $stopped;
            ?>
          </strong>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body py-2">
          <div class="small text-muted"><?php echo _("Disk Usage"); ?></div>
          <strong>
            <?php
              $df = $__template_data['systemDf'];
              if (!empty($df) && isset($df['raw'])) {
                  echo htmlspecialchars($df['raw']);
              } elseif (!empty($df)) {
                  $first = is_object($df[0]) ? $df[0] : (object) $df[0];
                  echo htmlspecialchars($first->Size ?? $first->TotalCount ?? '—');
              } else {
                  echo '—';
              }
            ?>
          </strong>
        </div>
      </div>
    </div>
  </div><!-- /.row -->

  <!-- Auto-refresh toggle -->
  <div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="docker-auto-refresh">
    <label class="form-check-label" for="docker-auto-refresh"><?php echo _("Auto-refresh (10s)"); ?></label>
  </div>

  <!-- Containers table -->
  <div class="table-responsive">
    <table class="table table-striped table-hover" id="docker-containers-table">
      <thead>
        <tr>
          <th><?php echo _("Name"); ?></th>
          <th><?php echo _("Image"); ?></th>
          <th><?php echo _("Status"); ?></th>
          <th><?php echo _("Ports"); ?></th>
          <th><?php echo _("Uptime"); ?></th>
          <th><?php echo _("Actions"); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($__template_data['containers'])): ?>
        <tr>
          <td colspan="6" class="text-center text-muted"><?php echo _("No containers found"); ?></td>
        </tr>
        <?php else: ?>
          <?php foreach ($__template_data['containers'] as $container): ?>
          <tr id="container-row-<?php echo htmlspecialchars($container->ID ?? ''); ?>">
            <td><?php echo htmlspecialchars($container->Names ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container->Image ?? ''); ?></td>
            <td>
              <?php if (($container->State ?? '') === 'running'): ?>
                <span class="badge bg-success"><?php echo htmlspecialchars($container->State); ?></span>
              <?php else: ?>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($container->State ?? ''); ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($container->Ports ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container->Status ?? ''); ?></td>
            <td>
              <?php if (($container->State ?? '') === 'running'): ?>
                <button type="button" class="btn btn-sm btn-warning js-container-stop" data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>"><?php echo _("Stop"); ?></button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-success js-container-start" data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>"><?php echo _("Start"); ?></button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div><!-- /.tab-pane -->
