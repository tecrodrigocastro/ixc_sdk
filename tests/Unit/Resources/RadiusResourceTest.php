<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\RadiusResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class RadiusResourceTest extends TestCase
{
    public function test_get_conexoes_by_login_queries_username(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 1, 'username' => 'joao.silva']]]);
        $resource = new RadiusResource($http);

        $result = $resource->getConexoesByLogin('joao.silva', 10);

        $this->assertSame([['id' => 1, 'username' => 'joao.silva']], $result);
        $this->assertSame('/radacct', $http->lastEndpoint);
        $this->assertSame('radacct.username', $http->lastParams['qtype']);
        $this->assertSame('joao.silva', $http->lastParams['query']);
        $this->assertSame('10', $http->lastParams['rp']);
    }

    public function test_get_quedas_recentes_filters_by_stop_time_and_session_duration(): void
    {
        $http = new FakeHttpClient(['registros' => []]);
        $resource = new RadiusResource($http);

        $resource->getQuedasRecentes('2026-07-23 00:00:00', 600);

        $filters = $http->lastGridParam();
        $this->assertSame('radacct.acctstoptime', $filters[0]['TB']);
        $this->assertSame('2026-07-23 00:00:00', $filters[0]['P']);
        $this->assertSame('radacct.acctsessiontime', $filters[1]['TB']);
        $this->assertSame('600', $filters[1]['P']);
    }

    public function test_get_ultima_conexao_returns_first_record_or_empty_array(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 1, 'acctstarttime' => '2026-07-23 10:00:00']]]);
        $resource = new RadiusResource($http);

        $this->assertSame(['id' => 1, 'acctstarttime' => '2026-07-23 10:00:00'], $resource->getUltimaConexao('joao.silva'));

        $http->response = ['registros' => []];
        $this->assertSame([], $resource->getUltimaConexao('sem.conexao'));
    }
}
