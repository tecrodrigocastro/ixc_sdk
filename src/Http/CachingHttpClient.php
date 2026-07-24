<?php

namespace RedRodrigo\IxcSdk\Http;

use Psr\SimpleCache\CacheInterface;
use RedRodrigo\IxcSdk\Contracts\HttpClientInterface;

/**
 * Decorator que cacheia respostas de leitura via qualquer implementação PSR-16.
 *
 * Substitui os `Cache::remember()` que antes estavam espalhados e duplicados
 * dentro de repositórios individuais (ex: IxcOsRepository, IxcVeiculoRepository)
 * por um único ponto de cache, plugável em qualquer camada de transporte.
 *
 * A chave de cache é derivada do endpoint + parâmetros da requisição, então
 * chamadas com filtros diferentes nunca colidem.
 */
final class CachingHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly CacheInterface $cache,
        private readonly int $ttl = 300,
    ) {
    }

    public function get(string $endpoint, array $params): array
    {
        $key = $this->cacheKey($endpoint, $params);

        $cached = $this->cache->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->inner->get($endpoint, $params);

        $this->cache->set($key, $result, $this->ttl);

        return $result;
    }

    /**
     * Respostas binárias (ex: PDF de boleto) não são cacheadas — costumam
     * representar documentos financeiros recalculados a cada emissão.
     */
    public function getRaw(string $endpoint, array $params): string
    {
        return $this->inner->getRaw($endpoint, $params);
    }

    /**
     * Escrita nunca é cacheada — cada chamada precisa chegar de fato na API.
     */
    public function post(string $endpoint, array $fields): array
    {
        return $this->inner->post($endpoint, $fields);
    }

    private function cacheKey(string $endpoint, array $params): string
    {
        return 'ixc_sdk_'.md5($endpoint.'|'.serialize($params));
    }
}
