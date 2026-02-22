<?php

namespace RaspAP\Plugins\Docker;

class DockerHubClient
{
    private string $apiBase   = 'https://hub.docker.com/v2/search/repositories/';
    private int    $timeout   = 5;
    private int    $pageSize  = 10;

    public function search(string $query, int $page = 1): array
    {
        if (trim($query) === '') {
            return [
                'results'       => [],
                'total_count'   => 0,
                'error'         => true,
                'error_message' => 'Empty query',
            ];
        }

        $url = $this->apiBase
            . '?query='     . urlencode($query)
            . '&page_size=' . $this->pageSize
            . '&page='      . $page;

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method'  => 'GET',
                'header'  => 'User-Agent: RaspAP-Docker-Plugin/1.0',
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $lastError = error_get_last();
            return [
                'results'       => [],
                'total_count'   => 0,
                'error'         => true,
                'error_message' => $lastError['message'] ?? 'Request failed',
            ];
        }

        // Check HTTP response code from the superglobal populated by file_get_contents
        $responseCode = 0;
        if (!empty($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $m)) {
                    $responseCode = (int) $m[1];
                    break;
                }
            }
        }

        if ($responseCode !== 200) {
            return [
                'results'       => [],
                'total_count'   => 0,
                'error'         => true,
                'error_message' => 'HTTP error: ' . $responseCode,
            ];
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'results'       => [],
                'total_count'   => 0,
                'error'         => true,
                'error_message' => 'JSON decode error: ' . json_last_error_msg(),
            ];
        }

        return [
            'results'     => $this->normalizeResults($data),
            'total_count' => (int) ($data['count'] ?? 0),
            'page'        => $page,
            'error'       => false,
        ];
    }

    private function normalizeResults(array $raw): array
    {
        $items = $raw['results'] ?? [];

        return array_map(function (array $item): array {
            return [
                'name'        => $item['repo_name'] ?? '',
                'description' => $item['short_description'] ?? '',
                'stars'       => (int) ($item['star_count'] ?? 0),
                'is_official' => (bool) ($item['is_official'] ?? false),
                'pull_count'  => (int) ($item['pull_count'] ?? 0),
            ];
        }, $items);
    }
}
