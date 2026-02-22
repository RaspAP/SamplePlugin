<?php

/**
 * Docker
 *
 * @description A Docker container management plugin for RaspAP
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 * @see         src/RaspAP/Plugins/PluginInterface.php
 * @see         src/RaspAP/UI/Sidebar.php
 */

namespace RaspAP\Plugins\Docker;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

require_once __DIR__ . '/DockerService.php';

if (!defined('RASPI_DOCKER_CONFIG')) {
    define('RASPI_DOCKER_CONFIG', '/etc/raspap/docker');
}

class Docker implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private string $serviceStatus;
    private string $label;
    private string $icon;
    private string $binPath;

    /**
     * Initializes the Docker plugin
     *
     * @param string $pluginPath path to the plugins directory
     * @param string $pluginName name of this plugin
     */
    public function __construct(string $pluginPath, string $pluginName)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->serviceStatus = 'up';
        $this->label = 'Docker';
        $this->icon = 'fab fa-docker';
        $this->binPath = '/usr/bin/docker';

        if ($loaded = self::loadData()) {
            $this->serviceStatus = $loaded->serviceStatus;
        }
    }

    /**
     * Initializes Docker and creates a sidebar item
     *
     * @param Sidebar $sidebar an instance of the Sidebar
     * @see src/RaspAP/UI/Sidebar.php
     * @see https://fontawesome.com/icons
     */
    public function initialize(Sidebar $sidebar): void
    {
        $label    = $this->label;
        $icon     = $this->icon;
        $action   = 'plugin__' . $this->getName();
        $priority = 77;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by processing inputs and rendering a plugin template
     *
     * @param string $page the current page route
     * @return bool true if this plugin handled the page, false otherwise
     */
    public function handlePageAction(string $page): bool
    {
        if (strpos($page, '/plugin__' . $this->getName()) === 0) {
            $status = new \RaspAP\Messages\StatusMessage();

            if (!file_exists($this->binPath)) {
                $msg = 'Docker binary not found. See https://docs.docker.com/engine/install/debian/ to install Docker.';
                $status->addMessage($msg, 'warning');
                $daemonStatus = 'inactive';
                $containers = [];
                $images = [];
                $systemDf = [];
                $dockerVersion = '';
            } else {
                $dockerService = new DockerService();
                $daemonStatus = $dockerService->getDaemonStatus();
                $containers = $dockerService->getContainers();
                $images = $dockerService->getImages();
                $systemDf = $dockerService->getSystemDf();
                $dockerVersion = $dockerService->getDockerVersion();
                $composeProjects = $dockerService->getComposeProjects();
                $volumes = $dockerService->getVolumes();

                if (!RASPI_MONITOR_ENABLED && isset($_POST['saveCompose'])) {
                    $project = trim($_POST['compose_project'] ?? '');
                    $yaml = $_POST['compose_yaml'] ?? '';
                    if ($project !== '' && $yaml !== '') {
                        $dockerService->saveComposeFile($project, $yaml);
                    }
                }
            }

            $this->serviceStatus = ($daemonStatus === 'active') ? 'up' : 'down';

            $__template_data = [
                'title'          => $this->label,
                'description'    => _('A Docker container management plugin for RaspAP'),
                'author'         => 'RaspAP',
                'uri'            => 'https://github.com/RaspAP/',
                'icon'           => $this->icon,
                'serviceStatus'  => $this->serviceStatus,
                'serviceName'    => 'docker',
                'action'         => 'plugin__' . $this->getName(),
                'pluginName'     => $this->getName(),
                'daemonStatus'   => $daemonStatus,
                'containers'     => $containers,
                'systemDf'       => $systemDf,
                'dockerVersion'  => $dockerVersion,
                'images'         => $images,
                'composeProjects' => $composeProjects ?? [],
                'volumes'        => $volumes ?? [],
            ];

            echo $this->renderTemplate($this->templateMain, compact('status', '__template_data'));

            return true;
        }
        return false;
    }

    /**
     * Renders a template from inside the plugin directory
     *
     * @param string $templateName name of the template (without .php extension)
     * @param array  $__data       data to extract into the template scope
     * @return string rendered template output
     */
    public function renderTemplate(string $templateName, array $__data = []): string
    {
        $templateFile = $this->pluginPath . '/' . $this->getName() . '/templates/' . $templateName . '.php';

        if (!file_exists($templateFile)) {
            return "Template file {$templateFile} not found.";
        }

        extract($__data);
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Persists plugin data to a temporary file
     *
     * @note Data is written to /tmp and cleared on reboot. Not for long-term storage.
     * @note Plugins should avoid $_SESSION vars as these may conflict with other plugins.
     * @return void
     */
    public function persistData(): void
    {
        file_put_contents("/tmp/plugin__{$this->getName()}.data", serialize($this));
    }

    /**
     * Loads previously persisted plugin data
     *
     * @return self|null the deserialized instance, or null if none exists
     */
    public static function loadData(): ?self
    {
        $file = "/tmp/plugin__" . self::getName() . ".data";
        if (!file_exists($file)) {
            return null;
        }
        $data = unserialize(file_get_contents($file));
        if ($data instanceof self) {
            return $data;
        }
        return null;
    }

    /**
     * Returns the abbreviated class name used as the plugin identifier
     *
     * @return string plugin name
     */
    public static function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }
}
