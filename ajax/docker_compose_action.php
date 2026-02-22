<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerJobManager.php';

if (!defined('RASPI_DOCKER_CONFIG')) {
    define('RASPI_DOCKER_CONFIG', '/etc/raspap/docker');
}

$project = $_POST['project'] ?? null;
$action  = $_POST['action'] ?? null;

if ($project === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: project']);
    exit;
}

if (!preg_match('/^[A-Za-z0-9_-]+$/', $project)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid project name']);
    exit;
}

if ($action === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: action']);
    exit;
}

if (!in_array($action, ['up', 'down', 'restart'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$composeRoot = RASPI_DOCKER_CONFIG . '/compose';
$composePath = $composeRoot . '/' . $project . '/docker-compose.yml';
$realPath    = realpath($composePath);

if ($realPath === false || strpos($realPath, realpath($composeRoot) . '/') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid compose path']);
    exit;
}

switch ($action) {
    case 'up':
        $cmd = 'sudo /usr/bin/docker compose -f ' . escapeshellarg($realPath) . ' up -d';
        break;
    case 'down':
        $cmd = 'sudo /usr/bin/docker compose -f ' . escapeshellarg($realPath) . ' down';
        break;
    case 'restart':
        $cmd = 'sudo /usr/bin/docker compose -f ' . escapeshellarg($realPath) . ' restart';
        break;
}

$manager = new \RaspAP\Plugins\Docker\DockerJobManager();
$jobId   = $manager->startJob($cmd);
echo json_encode(['jobId' => $jobId]);
