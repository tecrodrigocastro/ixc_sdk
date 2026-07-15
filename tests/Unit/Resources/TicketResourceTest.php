<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\TicketResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class TicketResourceTest extends TestCase
{
    public function test_get_atendimentos_filters_by_today(): void
    {
        $http = new FakeHttpClient(['total' => 1, 'registros' => [['id' => 1]]]);
        $resource = new TicketResource($http);

        $resource->getAtendimentos();

        $this->assertSame('/su_ticket', $http->lastEndpoint);
        $this->assertSame([
            ['TB' => 'su_ticket.data_criacao', 'OP' => 'L', 'P' => date('Y-m-d')],
        ], $http->lastGridParam());
    }

    public function test_get_tickets_by_period_returns_items(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 1], ['id' => 2]]]);
        $resource = new TicketResource($http);

        $result = $resource->getTicketsByPeriod('2024-07-01', '2024-07-31');

        $this->assertSame([['id' => 1], ['id' => 2]], $result);
        $this->assertSame([
            ['TB' => 'su_ticket.data_criacao', 'OP' => '>=', 'P' => '2024-07-01'],
            ['TB' => 'su_ticket.data_criacao', 'OP' => '<=', 'P' => '2024-07-31'],
        ], $http->lastGridParam());
    }

    public function test_get_tickets_by_period_does_not_use_ge_le_operators(): void
    {
        // /su_ticket rejeita GE/LE no grid_param (a API responde com HTML de
        // erro em vez de JSON), ao contrário de su_oss_chamado. Regressão.
        $http = new FakeHttpClient(['registros' => []]);
        $resource = new TicketResource($http);

        $resource->getTicketsByPeriod('2024-07-01', '2024-07-31');

        $operators = array_column($http->lastGridParam(), 'OP');
        $this->assertNotContains('GE', $operators);
        $this->assertNotContains('LE', $operators);
    }
}
