<?php

namespace RedRodrigo\IxcSdk\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use RedRodrigo\IxcSdk\Contracts\HttpClientInterface;
use RedRodrigo\IxcSdk\Exceptions\IxcRequestException;
use RedRodrigo\IxcSdk\Exceptions\IxcResponseException;

/**
 * Implementação padrão de HttpClientInterface usando Guzzle.
 *
 * Autentica via HTTP Basic (usuário + token da API) e envia o cabeçalho
 * `ixcsoft: listar`, exigido pelo protocolo de listagem do IXC Soft.
 */
final class GuzzleHttpClient implements HttpClientInterface
{
    private readonly ClientInterface $http;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $userId,
        private readonly string $token,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client();
    }

    public function get(string $endpoint, array $params): array
    {
        $decoded = json_decode((string) $this->send($endpoint, $params)->getBody(), true);

        if (! is_array($decoded)) {
            throw new IxcResponseException('Resposta inválida da API do IXC Soft (JSON malformado ou vazio).');
        }

        return $decoded;
    }

    public function getRaw(string $endpoint, array $params): string
    {
        return (string) $this->send($endpoint, $params)->getBody();
    }

    public function post(string $endpoint, array $fields): array
    {
        $decoded = json_decode((string) $this->sendPost($endpoint, $fields)->getBody(), true);

        if (! is_array($decoded)) {
            throw new IxcResponseException('Resposta inválida da API do IXC Soft (JSON malformado ou vazio).');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function send(string $endpoint, array $params): ResponseInterface
    {
        try {
            return $this->http->request('GET', rtrim($this->baseUrl, '/').$endpoint, [
                RequestOptions::AUTH => [$this->userId, $this->token],
                RequestOptions::HEADERS => [
                    'Content-type' => ['application/json', 'multipart/form-data', 'application/x-www-form-urlencoded', 'application/pdf'],
                    'ixcsoft' => 'listar',
                ],
                RequestOptions::BODY => json_encode($params, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (GuzzleException $exception) {
            throw IxcRequestException::fromGuzzleException($exception);
        }
    }

    /**
     * Escrita (inserir/editar/ação) — diferente de send(), não envia o header
     * `ixcsoft: listar` (protocolo exclusivo de listagem) e o corpo é o mapa
     * de campos do recurso, sem o envelope qtype/query/oper.
     *
     * @param  array<string, mixed>  $fields
     */
    private function sendPost(string $endpoint, array $fields): ResponseInterface
    {
        try {
            return $this->http->request('POST', rtrim($this->baseUrl, '/').$endpoint, [
                RequestOptions::AUTH => [$this->userId, $this->token],
                RequestOptions::HEADERS => [
                    'Content-type' => 'application/json',
                ],
                RequestOptions::BODY => json_encode($fields, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (GuzzleException $exception) {
            throw IxcRequestException::fromGuzzleException($exception);
        }
    }
}
