<div class="tab-pane fade" id="docker-containers" role="tabpanel">

  <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
    <h5 class="mb-0"><?php echo _('Containers'); ?></h5>
    <button type="button" class="btn btn-primary btn-sm" id="docker-create-container-btn">
      <i class="fas fa-plus me-1"></i><?php echo _('Create Container'); ?>
    </button>
  </div>

  <table class="table table-sm table-hover" id="docker-all-containers-table">
    <thead>
      <tr>
        <th><?php echo _('Name'); ?></th>
        <th><?php echo _('Image'); ?></th>
        <th><?php echo _('Status'); ?></th>
        <th><?php echo _('Ports'); ?></th>
        <th><?php echo _('Created'); ?></th>
        <th><?php echo _('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($__template_data['containers'] as $container): ?>
      <tr id="all-container-row-<?php echo htmlspecialchars($container->ID ?? ''); ?>">
        <td><?php echo htmlspecialchars($container->Names ?? ''); ?></td>
        <td><?php echo htmlspecialchars($container->Image ?? ''); ?></td>
        <td>
          <span class="badge <?php echo ($container->State === 'running') ? 'bg-success' : 'bg-secondary'; ?>">
            <?php echo htmlspecialchars($container->Status ?? ''); ?>
          </span>
        </td>
        <td><?php echo htmlspecialchars($container->Ports ?? ''); ?></td>
        <td><?php echo htmlspecialchars($container->CreatedAt ?? ''); ?></td>
        <td>
          <?php if ($container->State === 'running'): ?>
          <button class="btn btn-sm btn-warning js-all-container-stop"
                  data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>">
            <?php echo _('Stop'); ?>
          </button>
          <?php else: ?>
          <button class="btn btn-sm btn-success js-all-container-start"
                  data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>">
            <?php echo _('Start'); ?>
          </button>
          <?php endif; ?>
          <button class="btn btn-sm btn-info js-container-inspect"
                  data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>"
                  data-bs-toggle="modal"
                  data-bs-target="#docker-inspect-modal">
            <?php echo _('Inspect'); ?>
          </button>
          <button class="btn btn-sm btn-secondary js-container-logs"
                  data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>"
                  data-name="<?php echo htmlspecialchars($container->Names ?? ''); ?>">
            <i class="fas fa-file-alt me-1"></i><?php echo _('Logs'); ?>
          </button>
          <button class="btn btn-sm btn-outline-danger js-all-container-delete"
                  data-id="<?php echo htmlspecialchars($container->ID ?? ''); ?>"
                  data-name="<?php echo htmlspecialchars($container->Names ?? ''); ?>">
            <?php echo _('Delete'); ?>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($__template_data['containers'])): ?>
      <tr><td colspan="6" class="text-center text-muted"><?php echo _('No containers found'); ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Inspect Modal -->
  <div class="modal fade" id="docker-inspect-modal" tabindex="-1" aria-labelledby="dockerInspectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dockerInspectModalLabel"><?php echo _('Container Inspect'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo _('Close'); ?>"></button>
        </div>
        <div class="modal-body">
          <pre id="docker-inspect-output" class="bg-dark text-light p-3 rounded" style="max-height:400px;overflow-y:auto;font-size:0.8em;"></pre>
        </div>
      </div>
    </div>
  </div><!-- /#docker-inspect-modal -->

  <!-- Logs Modal -->
  <div class="modal fade" id="docker-logs-modal" tabindex="-1" aria-labelledby="dockerLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dockerLogsModalLabel">
            <i class="fas fa-file-alt me-2"></i><?php echo _('Logs'); ?>: <span id="docker-logs-container-name"></span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo _('Close'); ?>"></button>
        </div>
        <div class="modal-body p-0">
          <pre id="docker-logs-output" class="bg-dark text-light p-3 mb-0" style="height:420px;overflow-y:auto;font-size:0.78em;white-space:pre-wrap;word-break:break-all;"></pre>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Close'); ?></button>
          <button type="button" class="btn btn-success" id="docker-logs-follow-btn">
            <i class="fas fa-satellite-dish me-1"></i><?php echo _('Follow Live'); ?>
          </button>
        </div>
      </div>
    </div>
  </div><!-- /#docker-logs-modal -->

  <!-- Delete Container Modal -->
  <div class="modal fade" id="docker-container-delete-modal" tabindex="-1" aria-labelledby="dockerContainerDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dockerContainerDeleteModalLabel"><?php echo _('Delete Container'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo _('Close'); ?>"></button>
        </div>
        <div class="modal-body">
          <p><?php echo _('Are you sure you want to delete container'); ?> <strong id="docker-container-delete-name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
          <button type="button" class="btn btn-danger" id="docker-container-delete-confirm"><?php echo _('Delete'); ?></button>
        </div>
      </div>
    </div>
  </div><!-- /#docker-container-delete-modal -->

  <!-- Create Container Modal -->
  <div class="modal fade" id="docker-create-container-modal" tabindex="-1" aria-labelledby="dockerCreateContainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dockerCreateContainerModalLabel">
            <i class="fab fa-docker me-2"></i><?php echo _('Create Container'); ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo _('Close'); ?>"></button>
        </div>
        <div class="modal-body">

          <!-- Sub-tab nav -->
          <ul class="nav nav-tabs mb-3" id="create-container-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link active" id="cc-basic-tab" href="#cc-basic" data-bs-toggle="tab" role="tab">
                <?php echo _('Basic'); ?>
              </a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" id="cc-mounts-tab" href="#cc-mounts" data-bs-toggle="tab" role="tab">
                <?php echo _('Mounts & Env'); ?>
              </a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" id="cc-advanced-tab" href="#cc-advanced" data-bs-toggle="tab" role="tab">
                <?php echo _('Advanced'); ?>
              </a>
            </li>
          </ul>

          <div class="tab-content" id="create-container-tabs-content">

            <!-- Basic tab -->
            <div class="tab-pane fade show active" id="cc-basic" role="tabpanel">
              <div class="mb-3">
                <label for="cc-image" class="form-label"><?php echo _('Image'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="cc-image" required placeholder="e.g. nginx:latest">
              </div>
              <div class="mb-3">
                <label for="cc-name" class="form-label"><?php echo _('Container Name'); ?> <span class="text-muted">(<?php echo _('optional'); ?>)</span></label>
                <input type="text" class="form-control" id="cc-name" placeholder="e.g. my-nginx">
              </div>
              <div class="mb-3">
                <label for="cc-restart" class="form-label"><?php echo _('Restart Policy'); ?></label>
                <select class="form-select" id="cc-restart">
                  <option value=""><?php echo _('No restart'); ?></option>
                  <option value="always"><?php echo _('Always'); ?></option>
                  <option value="unless-stopped"><?php echo _('Unless stopped'); ?></option>
                  <option value="on-failure"><?php echo _('On failure'); ?></option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo _('Port Mappings'); ?></label>
                <div id="cc-ports-container">
                  <div class="d-flex align-items-center gap-2 mb-2 cc-port-row">
                    <input type="text" class="form-control form-control-sm" name="host_port" placeholder="<?php echo _('Host port'); ?>">
                    <span>:</span>
                    <input type="text" class="form-control form-control-sm" name="container_port" placeholder="<?php echo _('Container port'); ?>">
                    <select class="form-select form-select-sm" name="proto" style="max-width:80px;">
                      <option value="tcp">tcp</option>
                      <option value="udp">udp</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cc-add-port">
                  <i class="fas fa-plus me-1"></i><?php echo _('Add Port'); ?>
                </button>
              </div>
            </div><!-- /#cc-basic -->

            <!-- Mounts & Env tab -->
            <div class="tab-pane fade" id="cc-mounts" role="tabpanel">
              <div class="mb-3">
                <label class="form-label"><?php echo _('Volume Mounts'); ?></label>
                <div id="cc-volumes-container">
                  <div class="d-flex align-items-center gap-2 mb-2 cc-volume-row">
                    <input type="text" class="form-control form-control-sm" name="host_path" placeholder="<?php echo _('Host path'); ?>">
                    <span>:</span>
                    <input type="text" class="form-control form-control-sm" name="container_path" placeholder="<?php echo _('Container path'); ?>">
                    <button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cc-add-volume">
                  <i class="fas fa-plus me-1"></i><?php echo _('Add Volume'); ?>
                </button>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo _('Environment Variables'); ?></label>
                <div id="cc-env-container">
                  <div class="d-flex align-items-center gap-2 mb-2 cc-env-row">
                    <input type="text" class="form-control form-control-sm" name="env_key" placeholder="KEY">
                    <span>=</span>
                    <input type="text" class="form-control form-control-sm" name="env_value" placeholder="VALUE">
                    <button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cc-add-env">
                  <i class="fas fa-plus me-1"></i><?php echo _('Add Variable'); ?>
                </button>
              </div>
              <div class="mb-3">
                <label for="cc-network" class="form-label"><?php echo _('Network Mode'); ?></label>
                <select class="form-select" id="cc-network">
                  <option value=""><?php echo _('Default bridge'); ?></option>
                  <option value="host">host</option>
                  <option value="none">none</option>
                  <option value="bridge">bridge</option>
                </select>
              </div>
            </div><!-- /#cc-mounts -->

            <!-- Advanced tab -->
            <div class="tab-pane fade" id="cc-advanced" role="tabpanel">
              <div class="mb-3">
                <label for="cc-entrypoint" class="form-label"><?php echo _('Entrypoint'); ?></label>
                <input type="text" class="form-control" id="cc-entrypoint" placeholder="e.g. /bin/sh">
              </div>
              <div class="mb-3">
                <label for="cc-cmd" class="form-label"><?php echo _('Command Override'); ?></label>
                <input type="text" class="form-control" id="cc-cmd" placeholder="e.g. -c 'echo hello'">
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo _('Labels'); ?></label>
                <div id="cc-labels-container">
                  <div class="d-flex align-items-center gap-2 mb-2 cc-label-row">
                    <input type="text" class="form-control form-control-sm" name="label_key" placeholder="key">
                    <span>=</span>
                    <input type="text" class="form-control form-control-sm" name="label_value" placeholder="value">
                    <button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cc-add-label">
                  <i class="fas fa-plus me-1"></i><?php echo _('Add Label'); ?>
                </button>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="cc-cpu" class="form-label"><?php echo _('CPU Limit'); ?></label>
                  <input type="number" class="form-control" id="cc-cpu" step="0.1" min="0" placeholder="e.g. 1.5">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="cc-memory" class="form-label"><?php echo _('Memory Limit (MB)'); ?></label>
                  <input type="number" class="form-control" id="cc-memory" min="0" placeholder="e.g. 512">
                </div>
              </div>
            </div><!-- /#cc-advanced -->

          </div><!-- /.tab-content -->

          <div id="cc-error" class="alert alert-danger d-none mt-2"></div>

        </div><!-- /.modal-body -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
          <button type="button" class="btn btn-primary" id="docker-create-container-confirm">
            <span id="cc-spinner" class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
            <?php echo _('Create'); ?>
          </button>
        </div>
      </div>
    </div>
  </div><!-- /#docker-create-container-modal -->

</div><!-- /.tab-pane -->
