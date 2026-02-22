<?php

namespace RaspAP\Plugins\Docker;

class DockerJobManager
{
    private string $tmpDir = '/tmp';

    public function startJob(string $cmd): string
    {
        $jobId   = uniqid('docker_', true);
        $logFile = "/tmp/docker_job_{$jobId}.log";
        $pidFile = "/tmp/docker_job_{$jobId}.pid";

        $fullCmd = $cmd . ' > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
        exec($fullCmd, $output, $exitCode);

        file_put_contents($pidFile, trim($output[0] ?? ''));

        return $jobId;
    }

    public function getJobStatus(string $jobId): array
    {
        if (!preg_match('/^docker_[a-zA-Z0-9_.]+$/', $jobId)) {
            return ['running' => false, 'output' => '', 'done' => true];
        }

        $logFile = "/tmp/docker_job_{$jobId}.log";
        $pidFile = "/tmp/docker_job_{$jobId}.pid";

        if (!file_exists($pidFile)) {
            return [
                'running' => false,
                'output'  => file_get_contents($logFile) ?: '',
                'done'    => true,
            ];
        }

        $pid     = (int) trim(file_get_contents($pidFile));
        $running = ($pid > 0) && posix_kill($pid, 0);
        $output  = file_get_contents($logFile) ?: '';

        return [
            'running' => $running,
            'output'  => $output,
            'done'    => !$running,
        ];
    }

    public function cleanupJob(string $jobId): void
    {
        if (!preg_match('/^docker_[a-zA-Z0-9_.]+$/', $jobId)) {
            return;
        }

        $logFile = "/tmp/docker_job_{$jobId}.log";
        $pidFile = "/tmp/docker_job_{$jobId}.pid";

        if (file_exists($logFile)) {
            unlink($logFile);
        }
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }
}
