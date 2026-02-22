<?php

namespace RaspAP\Plugins\Docker;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

require_once __DIR__ . '/DockerService.php';

class Docker implements PluginInterface
{
    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private string $serviceStatus;
    private string $label;
    private string $icon;
    private string $binPath;

    public function __construct(string $pluginPath, string $pluginName)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->serviceStatus = 'up';
        $this->label = 'Docker';
        $this->icon = 'fab fa-docker';
        $this->binPath = '/usr/bin/docker';
        $this->loadData();
    }

    public function initialize(Sidebar $sidebar): void
    {
        $sidebar->addItem('Docker', $this->icon, 'plugin__Docker', 77);
    }

    public function handlePageAction(string $page): bool
    {
        if (strpos($page, '/plugin__' . $this->getName()) !== 0) {
            return false;
        }

        $status = new \RaspAP\Messages\StatusMessage();

        if (!file_exists($this->binPath)) {
            $status->addMessage('Docker binary not found. See https://docs.docker.com/engine/install/debian/ to install Docker.', 'warning');
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

    public function renderTemplate(string $templateName, array $__data = []): string
    {
        $templateFile = $this->pluginPath . '/' . $this->getName() . '/templates/' . $templateName . '.php';

        if (!file_exists($templateFile)) {
            return '';
        }

        extract($__data);
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    public function persistData(): void
    {
        file_put_contents("/tmp/plugin__{$this->getName()}.data", serialize($this));
    }

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

    public static function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }
}
