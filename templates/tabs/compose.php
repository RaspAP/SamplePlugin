<!-- compose tab -->
<div class="tab-pane fade" id="docker-compose" role="tabpanel">
  <div class="d-flex align-items-center justify-content-between mt-3 mb-3">
    <h6 class="mb-0"><?php echo _('Compose Projects'); ?></h6>
    <div>
      <button type="button" id="docker-compose-upload-btn" class="btn btn-outline-secondary btn-sm me-1"
              onclick="document.getElementById('docker-compose-file-input').click()">
        <i class="fas fa-upload fa-fw"></i> <?php echo _('Upload'); ?>
      </button>
      <input type="file" id="docker-compose-file-input" class="d-none" accept=".yml,.yaml">
      <button type="button" id="docker-compose-new-btn" class="btn btn-outline-primary btn-sm"
              data-bs-toggle="modal" data-bs-target="#docker-compose-new-modal">
        <i class="fas fa-plus fa-fw"></i> <?php echo _('New Project'); ?>
      </button>
    </div>
  </div>

  <?php if (empty($__template_data['composeProjects'])): ?>
    <div class="text-center py-5 text-muted">
      <i class="fab fa-docker fa-3x mb-3 d-block"></i>
      <?php echo _('No Compose projects found. Create a new project or upload a docker-compose.yml file.'); ?>
    </div>
  <?php else: ?>
    <?php foreach ($__template_data['composeProjects'] as $project): ?>
      <?php $pname = htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
      <div class="card mb-3" id="compose-project-<?php echo $pname; ?>">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <div class="d-flex align-items-center gap-2">
            <i class="fab fa-docker text-primary"></i>
            <strong><?php echo $pname; ?></strong>
            <span class="badge bg-secondary" id="compose-status-<?php echo $pname; ?>">unknown</span>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-success js-compose-up"
                    data-project="<?php echo $pname; ?>" title="<?php echo _('Up'); ?>">
              <i class="fas fa-play fa-fw"></i>
            </button>
            <button type="button" class="btn btn-outline-warning js-compose-down"
                    data-project="<?php echo $pname; ?>" title="<?php echo _('Down'); ?>">
              <i class="fas fa-stop fa-fw"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary js-compose-restart"
                    data-project="<?php echo $pname; ?>" title="<?php echo _('Restart'); ?>">
              <i class="fas fa-redo fa-fw"></i>
            </button>
            <button type="button" class="btn btn-outline-primary js-compose-edit"
                    data-project="<?php echo $pname; ?>"
                    data-yaml="<?php echo htmlspecialchars($project['yaml'], ENT_QUOTES, 'UTF-8'); ?>"
                    title="<?php echo _('Edit'); ?>">
              <i class="fas fa-edit fa-fw"></i>
            </button>
            <button type="button" class="btn btn-outline-danger js-compose-delete"
                    data-project="<?php echo $pname; ?>" title="<?php echo _('Delete'); ?>">
              <i class="fas fa-trash fa-fw"></i>
            </button>
          </div>
        </div>

        <!-- Log panel (populated by JS via streaming output) -->
        <div id="compose-log-<?php echo $pname; ?>" class="compose-log-panel d-none">
          <div class="card-body p-2 bg-dark">
            <pre class="compose-log-output text-light mb-0" style="max-height:200px;overflow-y:auto;font-size:0.8rem;"></pre>
          </div>
        </div>

        <!-- Inline YAML editor -->
        <div id="compose-editor-<?php echo $pname; ?>" class="compose-editor-panel d-none">
          <div class="card-body">
            <form method="post" action="?page=<?php echo htmlspecialchars($__template_data['action'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <input type="hidden" name="compose_project" value="<?php echo $pname; ?>">
              <div class="mb-2">
                <textarea name="compose_yaml" class="form-control font-monospace"
                          rows="16" spellcheck="false"><?php echo htmlspecialchars($project['yaml'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" name="saveCompose" class="btn btn-primary btn-sm">
                  <i class="fas fa-save fa-fw"></i> <?php echo _('Save'); ?>
                </button>
                <button type="button" class="btn btn-secondary btn-sm js-compose-editor-cancel"
                        data-project="<?php echo $pname; ?>">
                  <?php echo _('Cancel'); ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div><!-- /.tab-pane -->

<!-- New Project modal -->
<div class="modal fade" id="docker-compose-new-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo _('New Compose Project'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="compose-new-name" class="form-label"><?php echo _('Project Name'); ?></label>
          <input type="text" class="form-control" id="compose-new-name"
                 placeholder="my-project" pattern="[a-zA-Z0-9_-]+"
                 title="<?php echo _('Letters, numbers, hyphens and underscores only'); ?>">
        </div>
        <div class="mb-3">
          <label for="compose-new-yaml" class="form-label"><?php echo _('docker-compose.yml'); ?></label>
          <textarea class="form-control font-monospace" id="compose-new-yaml" rows="14"
                    spellcheck="false" placeholder="version: '3'&#10;services:&#10;  app:&#10;    image: nginx"></textarea>
        </div>
        <div id="compose-new-error" class="alert alert-danger d-none" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
        <button type="button" class="btn btn-primary" id="docker-compose-new-confirm">
          <i class="fas fa-plus fa-fw"></i> <?php echo _('Create'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="docker-compose-delete-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo _('Delete Compose Project'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><?php echo _('Are you sure you want to permanently delete this Compose project? This cannot be undone.'); ?></p>
        <input type="hidden" id="compose-delete-project-name" value="">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo _('Cancel'); ?></button>
        <button type="button" class="btn btn-danger" id="docker-compose-delete-confirm">
          <i class="fas fa-trash fa-fw"></i> <?php echo _('Delete Project'); ?>
        </button>
      </div>
    </div>
  </div>
</div>
