<?php

namespace RaspAP\Plugins\Docker;

/**
 * DockerHubClient
 *
 * @description Docker Hub API client for searching public repositories
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 */

/**
 * Wraps the Docker Hub v2 search API with response normalization
 * and HTTP error handling.
 */
class DockerHubClient
{
    private string $apiBase   = 'https://hub.docker.com/v2/search/repositories/';
    private int    $timeout   = 5;
    private int    $pageSize  = 10;

    /**
     * Searches Docker Hub for public repositories matching a query
     *
     * @param string $query search string (must not be empty)
     * @param int    $page  page number for pagination (1-based)
     * @return array{results: array, total_count: int, page: int, error: bool, error_message?: string}
     */
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
