<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerJobManager.php';

$image = trim($_POST['image'] ?? '');

if ($image === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: image']);
    exit;
}

$cmd     = 'sudo /usr/bin/docker pull ' . escapeshellarg($image);
$manager = new \RaspAP\Plugins\Docker\DockerJobManager();
$jobId   = $manager->startJob($cmd);
echo json_encode(['jobId' => $jobId]);
