<!-- about tab -->
<div class="tab-pane fade" id="docker-about" role="tabpanel">
  <h4 class="mt-3 mb-3"><?php echo _("About"); ?></h4>
  <dl class="row">
    <dt class="col-sm-3"><?php echo _("Plugin"); ?></dt>
    <dd class="col-sm-9">Docker</dd>

    <dt class="col-sm-3"><?php echo _("Version"); ?></dt>
    <dd class="col-sm-9">v1.0.0</dd>

    <dt class="col-sm-3"><?php echo _("Description"); ?></dt>
    <dd class="col-sm-9"><?php echo htmlspecialchars($__template_data['description']); ?></dd>

    <dt class="col-sm-3"><?php echo _("Author"); ?></dt>
    <dd class="col-sm-9"><a href="<?php echo htmlspecialchars($__template_data['uri']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($__template_data['author']); ?></a></dd>

    <dt class="col-sm-3"><?php echo _("Docker CE"); ?></dt>
    <dd class="col-sm-9">
      <?php echo !empty($__template_data['dockerVersion']) ? htmlspecialchars($__template_data['dockerVersion']) : _("Not installed"); ?>
    </dd>

    <dt class="col-sm-3"><?php echo _("Documentation"); ?></dt>
    <dd class="col-sm-9"><a href="https://docs.raspap.com/docker" target="_blank" rel="noopener">docs.raspap.com/docker</a></dd>
  </dl>
  <div class="col-6 mb-3">
    GitHub <i class="fa-brands fa-github"></i> <a href="<?php echo htmlspecialchars($__template_data['uri']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($__template_data['pluginName']); ?></a>
  </div>
</div><!-- /.tab-pane -->
