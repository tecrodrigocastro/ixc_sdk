<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Funcionários — IXC Soft.
 *
 * Endpoint coberto:
 *   GET /funcionarios — cadastro de funcionários
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class FuncionarioResource extends AbstractResource
{
    /**
     * Funcionários ativos, ordenados por ID decrescente (mais recentes primeiro).
     *
     * @return array<string, mixed>
     */
    public function getAllFuncionarios(): array
    {
        $query = QueryBuilder::for('funcionarios.id')
            ->perPage(200)
            ->sortBy('funcionarios.id', 'desc')
            ->filter('funcionarios.ativo', 'L', 'S');

        return $this->query('/funcionarios', $query);
    }
}
