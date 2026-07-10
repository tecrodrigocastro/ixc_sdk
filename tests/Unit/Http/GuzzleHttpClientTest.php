<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Exceptions\IxcRequestException;
use RedRodrigo\IxcSdk\Exceptions\IxcResponseException;
use RedRodrigo\IxcSdk\Http\GuzzleHttpClient;

final class GuzzleHttpClientTest extends TestCase
{
    private function makeClient(MockHandler $mock): Client
    {
        return new Client(['handler' => HandlerStack::create($mock)]);
    }

    public function test_decodes_json_response_body(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['total' => 1, 'registros' => [['id' => 1]]])),
        ]);

        $client = new GuzzleHttpClient('https://ixc.example.com/webservice/v1', '129', 'secret', $this->makeClient($mock));

        $result = $client->get('/cliente', ['qtype' => 'cliente.id', 'query' => '1']);

        $this->assertSame(['total' => 1, 'registros' => [['id' => 1]]], $result);
    }

    public function test_wraps_transport_failures_in_ixc_request_exception(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', 'test')),
        ]);

        $client = new GuzzleHttpClient('https://ixc.example.com/webservice/v1', '129', 'secret', $this->makeClient($mock));

        $this->expectException(IxcRequestException::class);

        $client->get('/cliente', ['qtype' => 'cliente.id']);
    }

    public function test_throws_response_exception_when_body_is_not_json(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not json'),
        ]);

        $client = new GuzzleHttpClient('https://ixc.example.com/webservice/v1', '129', 'secret', $this->makeClient($mock));

        $this->expectException(IxcResponseException::class);

        $client->get('/cliente', ['qtype' => 'cliente.id']);
    }
}
