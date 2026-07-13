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

    public function test_get_all_clientes_does_not_filter_by_a_specific_query_value(): void
    {
        $http = new FakeHttpClient(['total' => 2, 'registros' => [['id' => 1], ['id' => 2]]]);
        $resource = new ClienteResource($http);

        $resource->getAllClientes(2);

        $this->assertSame('cliente.id', $http->lastParams['qtype']);
        $this->assertSame('', $http->lastParams['query']);
        $this->assertSame('2', $http->lastParams['page']);
        $this->assertSame([
            ['TB' => 'cliente.ativo', 'OP' => 'L', 'P' => 'S'],
        ], $http->lastGridParam());
    }

    public function test_get_cliente_by_id_returns_first_record_or_empty_array(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 10, 'razao' => 'Fulano']]]);
        $resource = new ClienteResource($http);

        $this->assertSame(['id' => 10, 'razao' => 'Fulano'], $resource->getClienteById(10));

        $http->response = ['registros' => []];
        $this->assertSame([], $resource->getClienteById(999));
    }

    public function test_get_clientes_aniversariantes_maps_to_simplified_shape(): void
    {
        $http = new FakeHttpClient([
            'registros' => [
                ['id' => '7', 'razao' => 'Fulano', 'whatsapp' => '119999', 'data_nascimento' => '1990-07-13'],
            ],
        ]);
        $resource = new ClienteResource($http);

        $result = $resource->getClientesAniversariantes(0);

        $this->assertSame([[
            'id' => '7',
            'id_cliente' => '7',
            'razao' => 'Fulano',
            'whatsapp' => '119999',
            'data_aniversario' => '1990-07-13',
        ]], $result);

        $filters = $http->lastGridParam();
        $this->assertSame('cliente.ativo', $filters[0]['TB']);
        $this->assertSame('MONTH(cliente.data_nascimento)', $filters[1]['TB']);
        $this->assertSame('DAY(cliente.data_nascimento)', $filters[2]['TB']);
    }
}
