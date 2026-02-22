<?php

namespace RaspAP\Plugins\Docker;

/**
 * DockerJobManager
 *
 * @description Manages long-running Docker background jobs (e.g. image pulls)
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 */

/**
 * Runs shell commands as background processes, tracking them by job ID.
 * Job output is written to /tmp log files and queried via polling.
 */
class DockerJobManager
{
    private string $tmpDir = '/tmp';

    /**
     * Starts a background job and returns a unique job ID
     *
     * @param string $cmd the shell command to run in the background
     * @return string job ID used to query status and clean up
     */
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

    /**
     * Returns the current status and output of a background job
     *
     * @param string $jobId job ID returned by startJob()
     * @return array{running: bool, output: string, done: bool}
     */
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

    /**
     * Removes the log and PID files for a completed job
     *
     * @param string $jobId job ID returned by startJob()
     * @return void
     */
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
