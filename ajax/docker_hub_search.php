<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';
require_once '../DockerHubClient.php';

$query = trim($_POST['query'] ?? '');

if ($query === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameter: query']);
    exit;
}

$page = (int) ($_POST['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$client = new \RaspAP\Plugins\Docker\DockerHubClient();
$result = $client->search($query, $page);
echo json_encode($result);
