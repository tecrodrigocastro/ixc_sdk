<?php

namespace RedRodrigo\IxcSdk\Tests\Support;

use RedRodrigo\IxcSdk\Contracts\HttpClientInterface;

/**
 * Test double que grava a última chamada feita e devolve uma resposta fixa,
 * usado para testar Resources sem depender de uma implementação HTTP real.
 */
final class FakeHttpClient implements HttpClientInterface
{
    public ?string $lastEndpoint = null;

    /** @var array<string, mixed>|null */
    public ?array $lastParams = null;

    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(public array $response = [])
    {
    }

    public function get(string $endpoint, array $params): array
    {
        $this->lastEndpoint = $endpoint;
        $this->lastParams = $params;

        return $this->response;
    }

    /**
     * @return list<array{TB: string, OP: string, P: string|int}>
     */
    public function lastGridParam(): array
    {
        return json_decode($this->lastParams['grid_param'] ?? '[]', true);
    }
}
