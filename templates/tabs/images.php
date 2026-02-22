<div class="tab-pane fade" id="docker-images" role="tabpanel">

  <h5 class="mt-3"><?php echo _('Local Images'); ?></h5>
  <table class="table table-sm table-hover" id="docker-images-table">
    <thead>
      <tr>
        <th><?php echo _('Repository'); ?></th>
        <th><?php echo _('Tag'); ?></th>
        <th><?php echo _('Image ID'); ?></th>
        <th><?php echo _('Size'); ?></th>
        <th><?php echo _('Created'); ?></th>
        <th><?php echo _('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($__template_data['images'] as $image): ?>
      <tr id="image-row-<?php echo htmlspecialchars($image->ID ?? ''); ?>">
        <td><?php echo htmlspecialchars($image->Repository ?? ''); ?></td>
        <td><?php echo htmlspecialchars($image->Tag ?? ''); ?></td>
        <td><?php echo htmlspecialchars(substr($image->ID ?? '', 0, 12)); ?></td>
        <td><?php echo htmlspecialchars($image->Size ?? ''); ?></td>
        <td><?php echo htmlspecialchars($image->CreatedAt ?? ''); ?></td>
        <td>
          <button class="btn btn-sm btn-outline-danger js-image-delete"
                  data-id="<?php echo htmlspecialchars($image->ID ?? ''); ?>"
                  data-name="<?php echo htmlspecialchars(($image->Repository ?? '') . ':' . ($image->Tag ?? '')); ?>">
            <i class="fas fa-trash-alt"></i> <?php echo _('Delete'); ?>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($__template_data['images'])): ?>
      <tr><td colspan="6" class="text-center text-muted"><?php echo _('No images found'); ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Delete Image Modal -->
  <div class="modal fade" id="docker-image-delete-modal" tabindex="-1" aria-labelledby="dockerImageDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dockerImageDeleteModalLabel"><?php echo _('Delete Image'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo _('Close'); ?>"></button>
        </div>
        <div class="modal-body">
          <p><?php echo _('Are you sure you want to delete image'); ?> <strong id="docker-image-delete-name"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
          <button type="button" class="btn btn-danger" id="docker-image-delete-confirm"><?php echo _('Delete'); ?></button>
        </div>
      </div>
    </div>
  </div><!-- /#docker-image-delete-modal -->

  <h5 class="mt-4"><?php echo _('Pull Image'); ?></h5>
  <div class="input-group mb-3">
    <input type="text" class="form-control" id="docker-hub-search-input"
           placeholder="<?php echo _('Search Docker Hub (e.g. nginx, redis)'); ?>">
    <button class="btn btn-outline-primary" type="button" id="docker-hub-search-btn">
      <i class="fas fa-search me-1"></i><?php echo _('Search'); ?>
    </button>
  </div>

  <div id="docker-hub-results" class="d-none">
    <table class="table table-sm table-hover" id="docker-hub-results-table">
      <thead>
        <tr>
          <th><?php echo _('Image'); ?></th>
          <th><?php echo _('Description'); ?></th>
          <th><?php echo _('Stars'); ?></th>
          <th><?php echo _('Official'); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody id="docker-hub-results-body"></tbody>
    </table>
    <div class="d-flex align-items-center gap-2 mt-2">
      <button class="btn btn-sm btn-outline-secondary" id="docker-hub-prev" disabled>← <?php echo _('Previous'); ?></button>
      <span id="docker-hub-page-info"></span>
      <button class="btn btn-sm btn-outline-secondary" id="docker-hub-next"><?php echo _('Next'); ?> →</button>
    </div>
  </div><!-- /#docker-hub-results -->

  <div id="docker-pull-log-panel" class="d-none mt-3">
    <div class="d-flex align-items-center gap-2 mb-2">
      <strong id="docker-pull-image-name"></strong>
      <span id="docker-pull-status" class="badge bg-secondary"><?php echo _('Pulling...'); ?></span>
    </div>
    <pre id="docker-pull-log" class="bg-dark text-light p-2 rounded" style="max-height:200px;overflow-y:auto;font-size:0.8em;"></pre>
  </div><!-- /#docker-pull-log-panel -->

</div><!-- /.tab-pane -->
