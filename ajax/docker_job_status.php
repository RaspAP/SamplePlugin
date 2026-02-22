<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerJobManager.php';

$jobId = $_POST['jobId'] ?? $_GET['jobId'] ?? null;

if ($jobId === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: jobId']);
    exit;
}

$manager = new \RaspAP\Plugins\Docker\DockerJobManager();
$status  = $manager->getJobStatus($jobId);

$cleanup = $_POST['cleanup'] ?? null;
if ($cleanup === 'true' || $cleanup === '1') {
    $manager->cleanupJob($jobId);
}

echo json_encode($status);
