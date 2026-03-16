<?php
require_once '../../../includes/autoload.php';
require_once '../../../includes/CSRF.php';
require_once '../../../includes/session.php';
require_once '../../../includes/config.php';
require_once '../../../includes/authenticate.php';

$id = $_GET['id'] ?? null;
if ($id === null || $id === '') {
    http_response_code(400);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

$cmd = 'sudo /usr/bin/docker logs --follow --tail=50 --timestamps ' . escapeshellarg($id) . ' 2>&1';
$proc = popen($cmd, 'r');

if ($proc === false) {
    echo "data: [error: could not start log stream]\n\n";
    flush();
    exit;
}

while (!feof($proc) && connection_status() === CONNECTION_NORMAL) {
    $line = fgets($proc);
    if ($line !== false && $line !== '') {
        echo 'data: ' . rtrim($line) . "\n\n";
        flush();
    }
}

pclose($proc);
