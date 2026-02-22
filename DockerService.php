<?php

namespace RaspAP\Plugins\Docker;

class DockerService
{
    private string $dockerBin = '/usr/bin/docker';
    private string $composePath;

    public function __construct()
    {
        $this->composePath = RASPI_DOCKER_CONFIG . '/compose';
    }

    // -------------------------------------------------------------------------
    // Read methods
    // -------------------------------------------------------------------------

    public function getContainers(): array
    {
        $cmd = "sudo /usr/bin/docker ps -a --format '{{json .}}'";
        exec($cmd, $lines, $exitCode);
        if ($exitCode !== 0) {
            return [];
        }
        return array_values(array_filter(array_map('json_decode', $lines)));
    }

    public function getImages(): array
    {
        $cmd = "sudo /usr/bin/docker images --format '{{json .}}'";
        exec($cmd, $lines, $exitCode);
        if ($exitCode !== 0) {
            return [];
        }
        return array_values(array_filter(array_map('json_decode', $lines)));
    }

    public function getVolumes(): array
    {
        exec("sudo /usr/bin/docker volume ls --format '{{json .}}'", $lines, $exitCode);
        if ($exitCode !== 0) {
            return [];
        }

        $volumes = array_values(array_filter(array_map('json_decode', $lines)));
        $result = [];

        foreach ($volumes as $vol) {
            $name = $vol->Name ?? null;
            if ($name === null) {
                continue;
            }

            exec('sudo /usr/bin/docker volume inspect ' . escapeshellarg($name), $inspectLines, $rc);
            $inspectData = json_decode(implode('', $inspectLines));
            $inspect = is_array($inspectData) ? ($inspectData[0] ?? null) : null;

            $entry = (array) $vol;
            if ($inspect !== null) {
                $entry['Mountpoint'] = $inspect->Mountpoint ?? null;
                $entry['Labels']     = $inspect->Labels ?? null;
                $entry['CreatedAt']  = $inspect->CreatedAt ?? null;
                $entry['Driver']     = $inspect->Driver ?? null;
            }

            $result[] = $entry;
        }

        return $result;
    }

    public function getSystemDf(): array
    {
        exec("sudo /usr/bin/docker system df --format '{{json .}}'", $lines, $exitCode);
        if ($exitCode !== 0) {
            return [];
        }

        $parsed = array_values(array_filter(array_map('json_decode', $lines)));
        if (!empty($parsed)) {
            return $parsed;
        }

        // Fallback: Docker may output a single JSON object or plain text
        $output = shell_exec('sudo /usr/bin/docker system df');
        return ['raw' => $output];
    }

    // -------------------------------------------------------------------------
    // Action methods
    // -------------------------------------------------------------------------

    public function containerAction(string $id, string $action): array
    {
        $allowed = ['start', 'stop', 'rm'];
        if (!in_array($action, $allowed, true)) {
            return ['success' => false, 'output' => 'Invalid action'];
        }

        $cmd = 'sudo /usr/bin/docker ' . $action . ' ' . escapeshellarg($id);
        exec($cmd, $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output'  => implode("\n", $output),
        ];
    }

    public function createContainer(array $params): array
    {
        if (empty($params['image'])) {
            return ['success' => false, 'container_id' => '', 'error' => 'Missing required parameter: image'];
        }

        $cmd = 'sudo /usr/bin/docker run -d';

        if (!empty($params['name'])) {
            $cmd .= ' --name ' . escapeshellarg($params['name']);
        }

        if (!empty($params['ports']) && is_array($params['ports'])) {
            foreach ($params['ports'] as $port) {
                $cmd .= ' -p ' . escapeshellarg($port);
            }
        }

        if (!empty($params['volumes']) && is_array($params['volumes'])) {
            foreach ($params['volumes'] as $vol) {
                $cmd .= ' -v ' . escapeshellarg($vol);
            }
        }

        if (!empty($params['env']) && is_array($params['env'])) {
            foreach ($params['env'] as $env) {
                $cmd .= ' -e ' . escapeshellarg($env);
            }
        }

        if (!empty($params['network'])) {
            $cmd .= ' --network ' . escapeshellarg($params['network']);
        }

        if (!empty($params['restart'])) {
            $cmd .= ' --restart ' . escapeshellarg($params['restart']);
        }

        if (!empty($params['entrypoint'])) {
            $cmd .= ' --entrypoint ' . escapeshellarg($params['entrypoint']);
        }

        if (!empty($params['labels']) && is_array($params['labels'])) {
            foreach ($params['labels'] as $label) {
                $cmd .= ' --label ' . escapeshellarg($label);
            }
        }

        if (!empty($params['cpu_limit'])) {
            $cmd .= ' --cpus ' . escapeshellarg((string) $params['cpu_limit']);
        }

        if (!empty($params['memory_limit'])) {
            $cmd .= ' --memory ' . escapeshellarg((string) $params['memory_limit'] . 'm');
        }

        $cmd .= ' ' . escapeshellarg($params['image']);

        if (!empty($params['cmd'])) {
            $cmd .= ' ' . escapeshellarg($params['cmd']);
        }

        exec($cmd, $output, $exitCode);

        return [
            'success'      => $exitCode === 0,
            'container_id' => trim($output[0] ?? ''),
            'error'        => $exitCode !== 0 ? implode("\n", $output) : '',
        ];
    }

    public function deleteImage(string $id): array
    {
        exec('sudo /usr/bin/docker rmi ' . escapeshellarg($id), $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output'  => implode("\n", $output),
        ];
    }

    public function createVolume(string $name, string $driver = 'local', array $labels = []): array
    {
        $cmd = 'sudo /usr/bin/docker volume create --driver ' . escapeshellarg($driver);

        foreach ($labels as $label) {
            $parts = explode('=', $label, 2);
            if (count($parts) === 2) {
                $cmd .= ' --label ' . escapeshellarg($parts[0]) . '=' . escapeshellarg($parts[1]);
            }
        }

        $cmd .= ' ' . escapeshellarg($name);
        exec($cmd, $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'name'    => trim($output[0] ?? ''),
            'error'   => $exitCode !== 0 ? implode("\n", $output) : '',
        ];
    }

    public function deleteVolume(string $name): array
    {
        exec('sudo /usr/bin/docker volume rm ' . escapeshellarg($name), $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output'  => implode("\n", $output),
        ];
    }

    // -------------------------------------------------------------------------
    // Inspect / status methods
    // -------------------------------------------------------------------------

    public function inspectContainer(string $id): string
    {
        $output = shell_exec('sudo /usr/bin/docker inspect ' . escapeshellarg($id));
        return $output ?? '';
    }

    public function getDaemonStatus(): string
    {
        $output = shell_exec('systemctl is-active docker');
        return trim($output ?? 'inactive');
    }

    public function startDockerDaemon(): array
    {
        exec('sudo /bin/systemctl start docker', $output, $exitCode);
        return ['success' => $exitCode === 0];
    }

    public function getDockerVersion(): string
    {
        if (!file_exists($this->dockerBin)) {
            return '';
        }
        $output = shell_exec('sudo /usr/bin/docker --version');
        return trim($output ?? '');
    }

    // -------------------------------------------------------------------------
    // Compose file management (filesystem only)
    // -------------------------------------------------------------------------

    public function getComposeProjects(): array
    {
        if (!is_dir($this->composePath)) {
            return [];
        }

        $entries = scandir($this->composePath);
        $projects = [];

        foreach ($entries as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $fullPath = $this->composePath . '/' . $dir;
            if (!is_dir($fullPath)) {
                continue;
            }

            $ymlPath = $fullPath . '/docker-compose.yml';
            if (!file_exists($ymlPath)) {
                continue;
            }

            $projects[] = [
                'name'     => $dir,
                'path'     => $fullPath,
                'yaml'     => file_get_contents($ymlPath),
                'modified' => filemtime($ymlPath),
            ];
        }

        return $projects;
    }

    public function saveComposeFile(string $project, string $yaml): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $project)) {
            return false;
        }

        $dir = $this->composePath . '/' . $project;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($dir . '/docker-compose.yml', $yaml) !== false;
    }

    public function deleteComposeProject(string $project): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $project)) {
            return false;
        }

        $dir = $this->composePath . '/' . $project;
        if (!is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
        return true;
    }

    // -------------------------------------------------------------------------
    // Volume file browser
    // -------------------------------------------------------------------------

    public function browseVolumePath(string $mountpoint, string $subpath = ''): array
    {
        $realMount = realpath($mountpoint);
        if ($realMount === false) {
            throw new \RuntimeException('Invalid mountpoint');
        }

        $candidate = $mountpoint . ($subpath !== '' ? '/' . $subpath : '');
        $realCandidate = realpath($candidate);
        if ($realCandidate === false) {
            throw new \RuntimeException('Path does not exist');
        }

        if (strpos($realCandidate, $realMount) !== 0) {
            throw new \RuntimeException('Path traversal detected');
        }

        exec('sudo /bin/ls -la ' . escapeshellarg($realCandidate), $lines, $exitCode);
        if ($exitCode !== 0) {
            return ['entries' => [], 'current_path' => $realCandidate, 'error' => 'ls failed'];
        }

        $entries = [];
        foreach ($lines as $line) {
            if (strpos($line, 'total ') === 0) {
                continue;
            }

            if (!preg_match('/^([dlrwx\-]{10})\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\S+\s+\S+\s+\S+)\s+(.+)$/', $line, $m)) {
                continue;
            }

            $name = $m[4];
            if ($name === '.' || $name === '..') {
                continue;
            }

            $typeChar = $m[1][0];
            if ($typeChar === 'd') {
                $type = 'dir';
            } elseif ($typeChar === 'l') {
                $type = 'link';
            } else {
                $type = 'file';
            }

            $entries[] = [
                'name'        => $name,
                'type'        => $type,
                'size'        => (int) $m[2],
                'modified'    => $m[3],
                'permissions' => $m[1],
            ];
        }

        return [
            'entries'      => $entries,
            'current_path' => $realCandidate,
            'error'        => '',
        ];
    }
}
