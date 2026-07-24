<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Log de conexão Radius — IXC Soft.
 *
 * Endpoint coberto:
 *   GET /radacct — histórico de conexões PPPoE (uma linha por sessão)
 *
 * Campos relevantes de retorno (nomes reais da tabela `radacct`):
 *   id (radacctid), username (login PPPoE), callingstationid (MAC),
 *   framedipaddress (IP atribuído), nasipaddress (concentrador),
 *   acctstarttime/acctstoptime (início/fim da sessão),
 *   acctsessiontime (duração em segundos), acctterminatecause (motivo do
 *   encerramento — "Lost-Carrier" repetido indica rompimento/atenuação de
 *   fibra; sessões com acctsessiontime baixo indicam microquedas).
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class RadiusResource extends AbstractResource
{
    /**
     * Histórico de conexões de um login específico, mais recente primeiro.
     *
     * @return list<array<string, mixed>>
     */
    public function getConexoesByLogin(string $login, int $perPage = 50): array
    {
        $query = QueryBuilder::for('radacct.username')
            ->query($login)
            ->perPage($perPage)
            ->sortBy('radacct.acctstarttime', 'desc');

        return $this->list('/radacct', $query)->items;
    }

    /**
     * Sessões encerradas desde $desde com duração menor que $duracaoMaximaSegundos
     * (microquedas), em toda a base — usado para monitoramento preditivo de
     * degradação de rede (varre todos os clientes, não um login específico).
     *
     * @param  string  $desde  Data/hora inicial (ex: '2026-07-23 00:00:00')
     * @return list<array<string, mixed>>
     */
    public function getQuedasRecentes(string $desde, int $duracaoMaximaSegundos = 600): array
    {
        $query = QueryBuilder::for('radacct.acctstoptime')
            ->perPage(2000)
            ->sortBy('radacct.acctstoptime', 'desc')
            ->filter('radacct.acctstoptime', '>=', $desde)
            ->filter('radacct.acctsessiontime', '<', (string) $duracaoMaximaSegundos);

        return $this->list('/radacct', $query)->items;
    }

    /**
     * Última sessão (encerrada ou em andamento) de um login.
     *
     * @return array<string, mixed>
     */
    public function getUltimaConexao(string $login): array
    {
        $query = QueryBuilder::for('radacct.username')
            ->query($login)
            ->perPage(1)
            ->sortBy('radacct.acctstarttime', 'desc');

        return $this->list('/radacct', $query)->first() ?? [];
    }
}
