<!-- volumes tab -->
<div class="tab-pane fade" id="docker-volumes" role="tabpanel">
  <div class="d-flex align-items-center justify-content-between mt-3 mb-3">
    <h6 class="mb-0"><?php echo _('Volumes'); ?></h6>
    <button type="button" class="btn btn-outline-primary btn-sm"
            data-bs-toggle="modal" data-bs-target="#docker-create-volume-modal">
      <i class="fas fa-plus fa-fw"></i> <?php echo _('Create Volume'); ?>
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle" id="docker-volumes-table">
      <thead class="table-light">
        <tr>
          <th><?php echo _('Name'); ?></th>
          <th><?php echo _('Driver'); ?></th>
          <th><?php echo _('Mountpoint'); ?></th>
          <th><?php echo _('Created'); ?></th>
          <th><?php echo _('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($__template_data['volumes'])): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              <?php echo _('No volumes found.'); ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($__template_data['volumes'] as $vol): ?>
            <?php
              $vname      = htmlspecialchars($vol['Name']       ?? '', ENT_QUOTES, 'UTF-8');
              $vdriver    = htmlspecialchars($vol['Driver']     ?? '', ENT_QUOTES, 'UTF-8');
              $vmount     = $vol['Mountpoint'] ?? '';
              $vmountDisp = htmlspecialchars(
                strlen($vmount) > 40 ? substr($vmount, 0, 40) . '...' : $vmount,
                ENT_QUOTES, 'UTF-8'
              );
              $vcreated   = htmlspecialchars($vol['CreatedAt']  ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <tr id="volume-row-<?php echo $vname; ?>">
              <td><?php echo $vname; ?></td>
              <td><?php echo $vdriver; ?></td>
              <td><code title="<?php echo htmlspecialchars($vmount, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $vmountDisp; ?></code></td>
              <td><?php echo $vcreated; ?></td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-secondary js-volume-inspect"
                          data-name="<?php echo $vname; ?>"
                          data-bs-toggle="modal" data-bs-target="#docker-volume-inspect-modal"
                          title="<?php echo _('Inspect'); ?>">
                    <i class="fas fa-search fa-fw"></i>
                  </button>
                  <button type="button" class="btn btn-outline-danger js-volume-delete"
                          data-name="<?php echo $vname; ?>"
                          title="<?php echo _('Delete'); ?>">
                    <i class="fas fa-trash fa-fw"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div><!-- /.tab-pane -->

<!-- Create Volume modal -->
<div class="modal fade" id="docker-create-volume-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo _('Create Volume'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="volume-create-name" class="form-label"><?php echo _('Volume Name'); ?></label>
          <input type="text" class="form-control" id="volume-create-name" placeholder="my-volume">
        </div>
        <div class="mb-3">
          <label for="volume-create-driver" class="form-label"><?php echo _('Driver'); ?></label>
          <select class="form-select" id="volume-create-driver">
            <option value="local">local</option>
            <option value="nfs">nfs</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label"><?php echo _('Labels'); ?></label>
          <div id="volume-labels-container">
            <div class="volume-label-row input-group input-group-sm mb-1">
              <input type="text" class="form-control" placeholder="<?php echo _('Key'); ?>">
              <input type="text" class="form-control" placeholder="<?php echo _('Value'); ?>">
              <button type="button" class="btn btn-outline-danger js-volume-label-remove">
                <i class="fas fa-times fa-fw"></i>
              </button>
            </div>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="volume-add-label">
            <i class="fas fa-plus fa-fw"></i> <?php echo _('Add Label'); ?>
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
        <button type="button" class="btn btn-primary" id="docker-volume-create-confirm">
          <i class="fas fa-plus fa-fw"></i> <?php echo _('Create'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Volume confirmation modal -->
<div class="modal fade" id="docker-volume-delete-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo _('Delete Volume'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><?php echo _('Are you sure you want to permanently delete this volume? This cannot be undone.'); ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
        <button type="button" class="btn btn-danger" id="docker-volume-delete-confirm" data-name="">
          <i class="fas fa-trash fa-fw"></i> <?php echo _('Delete Volume'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Inspect Volume modal -->
<div class="modal fade" id="docker-volume-inspect-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo _('Volume:'); ?> <span id="volume-inspect-name"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Inner nav tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#vi-details"
                    type="button" role="tab"><?php echo _('Details'); ?></button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#vi-files"
                    type="button" role="tab"><?php echo _('Files'); ?></button>
          </li>
        </ul>
        <div class="tab-content">
          <!-- Details pane: populated by JS (T7) via docker_volume_browse.php -->
          <div class="tab-pane fade show active" id="vi-details" role="tabpanel">
            <dl class="row" id="volume-inspect-details"></dl>
          </div>
          <!-- Files pane: breadcrumb + file table populated by JS (T7) -->
          <div class="tab-pane fade" id="vi-files" role="tabpanel">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb" id="volume-file-breadcrumb"></ol>
            </nav>
            <div class="table-responsive">
              <table class="table table-sm" id="volume-files-table">
                <thead class="table-light">
                  <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('Type'); ?></th>
                    <th><?php echo _('Size'); ?></th>
                    <th><?php echo _('Modified'); ?></th>
                  </tr>
                </thead>
                <tbody id="volume-files-body">
                  <tr>
                    <td colspan="4" class="text-muted">
                      <?php echo _('Click a volume to browse files'); ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
