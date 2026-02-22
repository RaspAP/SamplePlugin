<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerService.php';

$image = $_POST['image'] ?? null;

if (empty($image)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: image']);
    exit;
}

$params = [
    'image'        => $_POST['image'],
    'name'         => $_POST['name'] ?? '',
    'ports'        => json_decode($_POST['ports'] ?? '[]', true),
    'volumes'      => json_decode($_POST['volumes'] ?? '[]', true),
    'env'          => json_decode($_POST['env'] ?? '[]', true),
    'network'      => $_POST['network'] ?? '',
    'restart'      => $_POST['restart'] ?? '',
    'entrypoint'   => $_POST['entrypoint'] ?? '',
    'cmd'          => $_POST['cmd'] ?? '',
    'labels'       => json_decode($_POST['labels'] ?? '[]', true),
    'cpu_limit'    => $_POST['cpu_limit'] ?? '',
    'memory_limit' => $_POST['memory_limit'] ?? '',
];

$service = new \RaspAP\Plugins\Docker\DockerService();
$result  = $service->createContainer($params);
echo json_encode($result);
