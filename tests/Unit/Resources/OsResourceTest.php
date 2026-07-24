<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\OsResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class OsResourceTest extends TestCase
{
    public function test_get_all_assuntos_os_returns_items_from_su_oss_assunto(): void
    {
        $http = new FakeHttpClient([
            'total'     => 2,
            'registros' => [
                ['id' => '1', 'assunto' => 'INSTALAÇÃO'],
                ['id' => '2', 'assunto' => 'CLIENTE SEM INTERNET'],
            ],
        ]);
        $resource = new OsResource($http);

        $result = $resource->getAllAssuntosOs();

        $this->assertSame([
            ['id' => '1', 'assunto' => 'INSTALAÇÃO'],
            ['id' => '2', 'assunto' => 'CLIENTE SEM INTERNET'],
        ], $result);
        $this->assertSame('/su_oss_assunto', $http->lastEndpoint);
        $this->assertSame('su_oss_assunto.id', $http->lastParams['qtype']);
    }

    public function test_get_os_by_cliente_excludes_cancelled_using_not_equal_operator(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => '1', 'status' => 'F']]]);
        $resource = new OsResource($http);

        $resource->getOsByCliente('14750', '2026-07-01', '2026-07-31');

        // A API do IXC rejeita "<>" nesse campo (confirmado contra produção) —
        // "!=" é o operador que de fato funciona.
        $filters = $http->lastGridParam();
        $this->assertSame('su_oss_chamado.status', $filters[2]['TB']);
        $this->assertSame('!=', $filters[2]['OP']);
        $this->assertSame('C', $filters[2]['P']);
    }

    public function test_abrir_os_preventiva_posts_required_fields_to_su_oss_chamado(): void
    {
        $http = new FakeHttpClient(['type' => 'success', 'id' => '999']);
        $resource = new OsResource($http);

        $result = $resource->abrirOsPreventiva(
            idCliente: '2241',
            idAssunto: '12',
            mensagem: 'OS aberta automaticamente: 8 quedas curtas nas últimas 24h.',
        );

        $this->assertSame(['type' => 'success', 'id' => '999'], $result);
        $this->assertSame('/su_oss_chamado', $http->lastEndpoint);
        $this->assertSame('C', $http->lastParams['tipo']);
        $this->assertSame('2241', $http->lastParams['id_cliente']);
        $this->assertSame('12', $http->lastParams['id_assunto']);
        $this->assertSame('A', $http->lastParams['status']);
        $this->assertSame('M', $http->lastParams['origem_endereco']);
    }
}
