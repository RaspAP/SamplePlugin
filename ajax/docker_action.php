<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerService.php';

$action = $_POST['action'] ?? null;

if ($action === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: action']);
    exit;
}

$service = new \RaspAP\Plugins\Docker\DockerService();

switch ($action) {
    case 'container_start':
    case 'container_stop':
    case 'container_delete':
        $id = $_POST['id'] ?? null;
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameter: id']);
            exit;
        }
        $dockerAction = $action === 'container_start' ? 'start'
                      : ($action === 'container_stop' ? 'stop' : 'rm');
        $result = $service->containerAction($id, $dockerAction);
        echo json_encode($result);
        break;

    case 'container_inspect':
        $id = $_POST['id'] ?? null;
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameter: id']);
            exit;
        }
        $output = $service->inspectContainer($id);
        echo json_encode(['output' => $output]);
        break;

    case 'image_delete':
        $id = $_POST['id'] ?? null;
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameter: id']);
            exit;
        }
        $result = $service->deleteImage($id);
        echo json_encode($result);
        break;

    case 'volume_create':
        $name = $_POST['name'] ?? null;
        if ($name === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameter: name']);
            exit;
        }
        $driver = $_POST['driver'] ?? 'local';
        $labels = json_decode($_POST['labels'] ?? '[]', true);
        $result = $service->createVolume($name, $driver, $labels);
        echo json_encode($result);
        break;

    case 'volume_delete':
        $name = $_POST['name'] ?? null;
        if ($name === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameter: name']);
            exit;
        }
        $result = $service->deleteVolume($name);
        echo json_encode($result);
        break;

    case 'compose_delete':
        $project = $_POST['project'] ?? null;
        if ($project === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameter: project']);
            exit;
        }
        $deleted = $service->deleteComposeProject($project);
        echo json_encode(['success' => $deleted, 'error' => $deleted ? '' : 'Failed to delete compose project']);
        break;

    case 'status_summary':
        $daemonStatus = $service->getDaemonStatus();
        $containers   = $service->getContainers();
        $systemDf     = $service->getSystemDf();
        echo json_encode([
            'daemon_status' => $daemonStatus,
            'containers'    => $containers,
            'system_df'     => $systemDf,
        ]);
        break;

    case 'daemon_start':
        $result = $service->startDockerDaemon();
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        break;
}
