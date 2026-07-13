<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\ComercialResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class ComercialResourceTest extends TestCase
{
    public function test_get_contratos_ativos_sends_expected_grid_param_filters(): void
    {
        $http = new FakeHttpClient(['total' => 0, 'registros' => []]);
        $resource = new ComercialResource($http);

        $resource->getContratosAtivos('2024-07-01', '2024-07-31');

        $this->assertSame('/cliente_contrato', $http->lastEndpoint);
        $this->assertSame([
            ['TB' => 'cliente_contrato.status', 'OP' => '=', 'P' => 'A'],
            ['TB' => 'cliente_contrato.data_ativacao', 'OP' => '>=', 'P' => '2024-07-01'],
            ['TB' => 'cliente_contrato.data_ativacao', 'OP' => '<=', 'P' => '2024-07-31'],
            ['TB' => 'cliente_contrato.status_internet', 'OP' => '=', 'P' => 'A'],
        ], $http->lastGridParam());
    }

    public function test_get_vendedor_by_id_falls_back_to_default_name_when_not_found(): void
    {
        $http = new FakeHttpClient(['total' => 0, 'registros' => []]);
        $resource = new ComercialResource($http);

        $this->assertSame('Vendedor padrão', $resource->getVendedorById('999'));

        $http->response = ['total' => 1, 'registros' => [['id' => '5', 'nome' => 'João da Silva']]];
        $this->assertSame('João da Silva', $resource->getVendedorById('5'));
    }

    public function test_get_todos_vendedores_indexes_by_id(): void
    {
        $http = new FakeHttpClient([
            'registros' => [['id' => '5'], ['id' => '8']],
        ]);
        $resource = new ComercialResource($http);

        $this->assertSame([
            '5' => ['5'],
            '8' => ['8'],
        ], $resource->getTodosVendedores());
    }

    public function test_os_por_assunto_helpers_use_correct_id_assunto(): void
    {
        $http = new FakeHttpClient(['registros' => []]);
        $resource = new ComercialResource($http);

        $resource->getOsUpgrades('2024-07-01', '2024-07-31');
        $this->assertSame('25', $http->lastGridParam()[2]['P']);

        $resource->getOsCortesia100('2024-07-01', '2024-07-31');
        $this->assertSame('78', $http->lastGridParam()[2]['P']);
    }

    public function test_get_all_bloqueados_defaults_initial_date_to_today_when_omitted(): void
    {
        $http = new FakeHttpClient(['total' => 0, 'registros' => []]);
        $resource = new ComercialResource($http);

        $resource->getAllBloqueados();

        $today = date('Y-m-d');
        $filters = $http->lastGridParam();

        $this->assertSame('cliente_contrato.status_internet', $filters[0]['TB']);
        $this->assertSame('CA', $filters[0]['P']);
        $this->assertSame('cliente_contrato.status', $filters[1]['TB']);
        $this->assertSame('A', $filters[1]['P']);
        $this->assertSame($today, $filters[2]['P']);
        $this->assertSame($today, $filters[3]['P']);
    }

    public function test_get_all_bloqueados_accepts_explicit_initial_date(): void
    {
        $http = new FakeHttpClient(['total' => 0, 'registros' => []]);
        $resource = new ComercialResource($http);

        $resource->getAllBloqueados('2024-07-15');

        $filters = $http->lastGridParam();
        $this->assertSame('2024-07-15', $filters[2]['P']);
    }
}
