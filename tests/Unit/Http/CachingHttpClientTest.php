<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Http\CachingHttpClient;
use RedRodrigo\IxcSdk\Tests\Support\ArrayCache;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class CachingHttpClientTest extends TestCase
{
    public function test_caches_response_and_does_not_hit_inner_client_twice(): void
    {
        $inner = new FakeHttpClient(['total' => 1, 'registros' => [['id' => 1]]]);
        $cache = new ArrayCache();
        $client = new CachingHttpClient($inner, $cache, ttl: 60);

        $first = $client->get('/cliente', ['qtype' => 'cliente.id', 'query' => '1']);
        $inner->response = ['total' => 999, 'registros' => []];
        $second = $client->get('/cliente', ['qtype' => 'cliente.id', 'query' => '1']);

        $this->assertSame($first, $second);
        $this->assertSame(1, $cache->setCalls);
    }

    public function test_different_params_use_different_cache_keys(): void
    {
        $inner = new FakeHttpClient(['registros' => [['id' => 1]]]);
        $cache = new ArrayCache();
        $client = new CachingHttpClient($inner, $cache);

        $client->get('/cliente', ['qtype' => 'cliente.id', 'query' => '1']);
        $client->get('/cliente', ['qtype' => 'cliente.id', 'query' => '2']);

        $this->assertSame(2, $cache->setCalls);
    }

    public function test_get_raw_delegates_without_caching(): void
    {
        $inner = new FakeHttpClient();
        $inner->rawResponse = '%PDF-1.4 bytes';
        $cache = new ArrayCache();
        $client = new CachingHttpClient($inner, $cache);

        $result = $client->getRaw('/get_boleto', ['boletos' => 123]);

        $this->assertSame('%PDF-1.4 bytes', $result);
        $this->assertSame(0, $cache->setCalls);
    }
}
