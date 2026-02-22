<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerService.php';

$volumeName = $_POST['volume_name'] ?? null;

if ($volumeName === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: volume_name']);
    exit;
}

$subpath = $_POST['subpath'] ?? '';

$service = new \RaspAP\Plugins\Docker\DockerService();
$volumes = $service->getVolumes();

$mountpoint = null;
foreach ($volumes as $vol) {
    if (($vol['Name'] ?? null) === $volumeName) {
        $mountpoint = $vol['Mountpoint'] ?? null;
        break;
    }
}

if ($mountpoint === null || $mountpoint === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Volume not found']);
    exit;
}

try {
    $result = $service->browseVolumePath($mountpoint, $subpath);
} catch (\RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$currentPath = $result['current_path'];
$breadcrumb  = [['name' => basename($mountpoint), 'path' => $mountpoint]];

$rel = ltrim(substr($currentPath, strlen($mountpoint)), '/');
if ($rel !== '') {
    $segments    = explode('/', $rel);
    $accumulated = $mountpoint;
    foreach ($segments as $segment) {
        if ($segment === '') {
            continue;
        }
        $accumulated  .= '/' . $segment;
        $breadcrumb[] = ['name' => $segment, 'path' => $accumulated];
    }
}

echo json_encode([
    'entries'      => $result['entries'],
    'current_path' => $currentPath,
    'breadcrumb'   => $breadcrumb,
    'error'        => $result['error'],
]);
