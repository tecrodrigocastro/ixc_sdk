<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Financeiro — IXC Soft.
 *
 * Histórico de pagamentos, faturas em aberto e desbloqueio de confiança.
 *
 * Endpoints cobertos:
 *   GET /fn_areceber            — faturas a receber
 *   GET /desbloqueio_confianca  — desbloqueio de confiança
 *
 * Tabela fn_areceber — valores de status:
 *   'A' = Aberto | 'R' = Recebido (pago) | 'C' = Cancelado | 'V' = Vencido
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class FinanceiroResource extends AbstractResource
{
    /**
     * Histórico das últimas 20 faturas pagas de um cliente (mais recente primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getHistoricoPagamentos(int $idCliente): array
    {
        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->query($idCliente)
            ->perPage(20)
            ->sortBy('fn_areceber.data_vencimento', 'desc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'R');

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Todas as faturas em aberto de um cliente, ordenadas por vencimento (mais antiga primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getProximasFaturas(int $idCliente): array
    {
        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->query($idCliente)
            ->perPage(20)
            ->sortBy('fn_areceber.data_vencimento', 'asc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'A');

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Solicita o desbloqueio de confiança para um contrato bloqueado por inadimplência.
     *
     * @return array{type?: string, message?: string}
     */
    public function desbloqueioConfianca(int $idContrato): array
    {
        return $this->raw('/desbloqueio_confianca', ['id' => $idContrato]);
    }
}
