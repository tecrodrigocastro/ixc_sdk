<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Query\QueryBuilder;

final class QueryBuilderTest extends TestCase
{
    public function test_builds_minimal_params_with_defaults(): void
    {
        $params = QueryBuilder::for('cliente.id')->toArray();

        $this->assertSame([
            'qtype' => 'cliente.id',
            'query' => '',
            'oper' => '=',
            'page' => '1',
            'rp' => '200',
            'sortname' => 'cliente.id',
            'sortorder' => 'desc',
        ], $params);
    }

    public function test_query_operator_page_and_sort_are_configurable(): void
    {
        $params = QueryBuilder::for('cliente.razao')
            ->query('joao')
            ->operator('L')
            ->page(2)
            ->perPage(10)
            ->sortBy('cliente.razao', 'asc')
            ->toArray();

        $this->assertSame('joao', $params['query']);
        $this->assertSame('L', $params['oper']);
        $this->assertSame('2', $params['page']);
        $this->assertSame('10', $params['rp']);
        $this->assertSame('cliente.razao', $params['sortname']);
        $this->assertSame('asc', $params['sortorder']);
    }

    public function test_grid_param_is_only_present_when_filters_are_added(): void
    {
        $withoutFilters = QueryBuilder::for('cliente.id')->toArray();
        $this->assertArrayNotHasKey('grid_param', $withoutFilters);

        $withFilters = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->filter('cliente_contrato.status', '=', 'A')
            ->filter('cliente_contrato.data_ativacao', '>=', '2024-07-01')
            ->toArray();

        $this->assertArrayHasKey('grid_param', $withFilters);

        $decoded = json_decode($withFilters['grid_param'], true);

        $this->assertSame([
            ['TB' => 'cliente_contrato.status', 'OP' => '=', 'P' => 'A'],
            ['TB' => 'cliente_contrato.data_ativacao', 'OP' => '>=', 'P' => '2024-07-01'],
        ], $decoded);
    }
}
