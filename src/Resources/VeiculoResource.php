<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Veículos e frota — IXC Soft.
 *
 * Endpoints cobertos:
 *   GET /veiculos          — cadastro de veículos
 *   GET /veiculos_despesas — lançamentos de despesas de frota
 *
 * Dados de frota mudam pouco ao longo do dia — se quiser cache, envolva o
 * HttpClientInterface injetado com RedRodrigo\IxcSdk\Http\CachingHttpClient.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class VeiculoResource extends AbstractResource
{
    /**
     * Veículos cadastrados, filtrados por status (ordenado por id ASC).
     *
     * @param  string  $status  'A' = Ativo (padrão), 'I' = Inativo
     * @return list<array<string, mixed>>
     */
    public function getVeiculos(string $status = 'A', int $page = 1, int $rp = 200): array
    {
        $query = QueryBuilder::for('veiculos.status')
            ->query($status)
            ->page($page)
            ->perPage($rp)
            ->sortBy('veiculos.id', 'asc');

        return $this->list('/veiculos', $query)->items;
    }

    /**
     * Despesas de frota registradas em um período (mais recente primeiro).
     *
     * Para calcular o custo total por veículo, agrupe por id_veiculo e some os valores.
     *
     * @return list<array<string, mixed>>
     */
    public function getDespesasVeiculos(string $dataInicio, string $dataFim, int $page = 1, int $rp = 500): array
    {
        $query = QueryBuilder::for('veiculos_despesas.data')
            ->query($dataInicio)
            ->operator('>=')
            ->page($page)
            ->perPage($rp)
            ->sortBy('veiculos_despesas.data', 'desc')
            ->filter('veiculos_despesas.data', '<=', $dataFim);

        return $this->list('/veiculos_despesas', $query)->items;
    }
}
