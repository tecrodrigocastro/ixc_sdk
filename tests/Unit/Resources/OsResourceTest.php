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
}
