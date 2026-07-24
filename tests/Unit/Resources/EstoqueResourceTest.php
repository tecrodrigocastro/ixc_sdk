<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\EstoqueResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class EstoqueResourceTest extends TestCase
{
    public function test_get_all_itens_transferencia_paginates_by_id_desc(): void
    {
        $http = new FakeHttpClient(['registros' => [
            ['id' => '463127', 'id_produto' => '1318', 'qtde' => '1.000000000', 'id_transf_almox' => '67721'],
        ]]);
        $resource = new EstoqueResource($http);

        $result = $resource->getAllItensTransferencia(2, 500);

        $this->assertSame([['id' => '463127', 'id_produto' => '1318', 'qtde' => '1.000000000', 'id_transf_almox' => '67721']], $result);
        $this->assertSame('/transf_almox_item', $http->lastEndpoint);
        $this->assertSame('transf_almox_item.id', $http->lastParams['qtype']);
        $this->assertSame('>=', $http->lastParams['oper']);
        $this->assertSame('2', $http->lastParams['page']);
        $this->assertSame('500', $http->lastParams['rp']);
        $this->assertSame('desc', $http->lastParams['sortorder']);
    }
}
