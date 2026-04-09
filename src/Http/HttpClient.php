<?php

namespace Emitte\DfeIob\Http;

use Emitte\DfeIob\Exceptions\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Wrapper sobre o Guzzle para os endpoints da IOB.
 *
 * Todos os métodos lançam ApiException em caso de erros HTTP (4xx/5xx)
 * ou de conectividade.
 */
class HttpClient
{
    private GuzzleClient $guzzle;

    /** @param array<string, mixed> $defaultHeaders Headers padrão para todas as requisições */
    public function __construct(
        private readonly string $baseUrl,
        private readonly array $defaultHeaders = [],
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 10,
    ) {
        $this->guzzle = new GuzzleClient([
            'base_uri'        => rtrim($baseUrl, '/'),
            'timeout'         => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors'     => false,
        ]);
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        $options = ['headers' => $this->mergeHeaders($headers)];

        if (!empty($query)) {
            $options['query'] = $query;
        }

        return $this->send('GET', $path, $options);
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = [], array $headers = [], array $query = []): array
    {
        $options = [
            'headers' => $this->mergeHeaders($headers),
            'json'    => $body,
        ];

        if (!empty($query)) {
            $options['query'] = $query;
        }

        return $this->send('POST', $path, $options);
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function put(string $path, array $body = [], array $headers = []): array
    {
        return $this->send('PUT', $path, [
            'headers' => $this->mergeHeaders($headers),
            'json'    => $body,
        ]);
    }

    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function delete(string $path, array $headers = [], array $query = []): array
    {
        $options = ['headers' => $this->mergeHeaders($headers)];

        if (!empty($query)) {
            $options['query'] = $query;
        }

        return $this->send('DELETE', $path, $options);
    }

    /**
     * Envia uma requisição multipart/form-data (upload de arquivo).
     *
     * @param array<array{name: string, contents: mixed, filename?: string}> $multipart
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function postMultipart(string $path, array $multipart, array $headers = []): array
    {
        $mergedHeaders = $this->mergeHeaders($headers);
        unset($mergedHeaders['Content-Type']); // o Guzzle define o boundary automaticamente

        return $this->send('POST', $path, [
            'headers'    => $mergedHeaders,
            'multipart'  => $multipart,
        ]);
    }

    /**
     * Retorna o corpo bruto (ex: XML ou PDF) como string.
     *
     * @param array<string, mixed> $headers
     */
    public function getRaw(string $path, array $headers = []): string
    {
        try {
            $response = $this->guzzle->get($path, [
                'headers' => $this->mergeHeaders($headers),
            ]);
        } catch (GuzzleException $e) {
            throw new ApiException($e->getMessage(), 0, null, $e);
        }

        $this->assertSuccessful($response);

        return (string) $response->getBody();
    }

    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $options): array
    {
        try {
            $response = $this->guzzle->request($method, $path, $options);
        } catch (GuzzleException $e) {
            throw new ApiException($e->getMessage(), 0, null, $e);
        }

        $this->assertSuccessful($response);

        $body = (string) $response->getBody();

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw' => $body];
        }

        return $decoded;
    }

    private function assertSuccessful(ResponseInterface $response): void
    {
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return;
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        throw new ApiException(
            $decoded['message'] ?? $decoded['title'] ?? "Erro HTTP {$status}",
            $status,
            is_array($decoded) ? $decoded : null,
        );
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function mergeHeaders(array $extra): array
    {
        return array_merge($this->defaultHeaders, array_filter($extra));
    }
}
