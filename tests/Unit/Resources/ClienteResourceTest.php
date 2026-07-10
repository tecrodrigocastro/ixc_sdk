<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\ClienteResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class ClienteResourceTest extends TestCase
{
    public function test_search_cliente_by_id_uses_exact_match(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => '1805', 'razao' => 'Fulano']]]);
        $resource = new ClienteResource($http);

        $result = $resource->searchCliente('1805');

        $this->assertSame([['id' => '1805', 'razao' => 'Fulano']], $result);
        $this->assertSame('/cliente', $http->lastEndpoint);
        $this->assertSame('cliente.id', $http->lastParams['qtype']);
        $this->assertSame('=', $http->lastParams['oper']);
    }

    public function test_search_cliente_by_name_uses_like_match(): void
    {
        $http = new FakeHttpClient(['registros' => []]);
        $resource = new ClienteResource($http);

        $resource->searchCliente('Fulano');

        $this->assertSame('cliente.razao', $http->lastParams['qtype']);
        $this->assertSame('L', $http->lastParams['oper']);
    }

    public function test_get_contrato_by_id_returns_first_record_or_empty_array(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 5, 'status' => 'A']]]);
        $resource = new ClienteResource($http);

        $this->assertSame(['id' => 5, 'status' => 'A'], $resource->getContratoById(5));

        $http->response = ['registros' => []];
        $this->assertSame([], $resource->getContratoById(999));
    }

    public function test_get_onu_serial_by_contrato_returns_null_when_field_is_empty(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 1, 'onu_mac' => '']]]);
        $resource = new ClienteResource($http);

        $this->assertNull($resource->getOnuSerialByContrato('123'));

        $http->response = ['registros' => [['id' => 1, 'onu_mac' => 'AA:BB:CC']]];
        $this->assertSame('AA:BB:CC', $resource->getOnuSerialByContrato('123'));

        $http->response = ['registros' => []];
        $this->assertNull($resource->getOnuSerialByContrato('123'));
    }
}
