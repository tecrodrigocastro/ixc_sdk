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

    private function cacheKey(string $endpoint, array $params): string
    {
        return 'ixc_sdk_'.md5($endpoint.'|'.serialize($params));
    }
}
