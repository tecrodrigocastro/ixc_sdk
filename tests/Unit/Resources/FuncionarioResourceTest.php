<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\FuncionarioResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class FuncionarioResourceTest extends TestCase
{
    public function test_get_all_funcionarios_filters_by_ativo(): void
    {
        $http = new FakeHttpClient(['total' => 1, 'registros' => [['id' => 1, 'nome' => 'João']]]);
        $resource = new FuncionarioResource($http);

        $result = $resource->getAllFuncionarios();

        $this->assertSame(['total' => 1, 'registros' => [['id' => 1, 'nome' => 'João']]], $result);
        $this->assertSame('/funcionarios', $http->lastEndpoint);
        $this->assertSame([
            ['TB' => 'funcionarios.ativo', 'OP' => 'L', 'P' => 'S'],
        ], $http->lastGridParam());
    }
}
