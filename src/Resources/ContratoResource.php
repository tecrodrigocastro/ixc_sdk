<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Contratos de venda e serviços adicionais — IXC Soft.
 *
 * Endpoints cobertos:
 *   GET /vd_contratos              — planos/contratos de venda
 *   GET /tv_usuarios               — dados de TV por contrato
 *   GET /cliente_contrato_historico — histórico de eventos do contrato (renovação, mudanças)
 *   GET /cliente_contrato_descontos — descontos aplicados ao contrato
 *   GET /cliente_contrato_servicos  — acréscimos/descontos de serviço ('A'/'D' em tipo_acres_desc)
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class ContratoResource extends AbstractResource
{
    /**
     * Detalhes completos de um plano/contrato de venda pelo seu ID
     * (nome do plano, valor, velocidade contratados).
     *
     * @return array<string, mixed>
     */
    public function getDetalhesContrato(string $idVdContrato): array
    {
        $query = QueryBuilder::for('vd_contratos.id')
            ->query($idVdContrato)
            ->perPage(20)
            ->sortBy('vd_contratos.id', 'desc');

        return $this->list('/vd_contratos', $query)->first() ?? [];
    }

    /**
     * Dados de TV vinculados a um contrato de internet (vazio se não houver TV).
     *
     * @return list<array<string, mixed>>
     */
    public function getTvInfo(int $idContrato): array
    {
        $query = QueryBuilder::for('tv_usuarios.id_contrato')
            ->query($idContrato)
            ->perPage(20)
            ->sortBy('tv_usuarios.id', 'desc');

        return $this->list('/tv_usuarios', $query)->items;
    }

    /**
     * Histórico de eventos de um contrato (renovações, mudanças de plano
     * etc.), mais recente primeiro — usado para calcular o Lifespan do
     * cliente na classificação de risco do módulo de Retenção.
     *
     * @return list<array<string, mixed>>
     */
    public function getHistoricoContrato(string $idContrato): array
    {
        $query = QueryBuilder::for('cliente_contrato_historico.id_contrato')
            ->query($idContrato)
            ->perPage(200)
            ->sortBy('cliente_contrato_historico.id', 'desc');

        return $this->list('/cliente_contrato_historico', $query)->items;
    }

    /**
     * Descontos aplicados a um contrato.
     *
     * @return list<array<string, mixed>>
     */
    public function getDescontosContrato(string $idContrato): array
    {
        $query = QueryBuilder::for('cliente_contrato_descontos.id_contrato')
            ->query($idContrato)
            ->perPage(200)
            ->sortBy('cliente_contrato_descontos.id', 'desc');

        return $this->list('/cliente_contrato_descontos', $query)->items;
    }

    /**
     * Serviços adicionais (acréscimos/descontos) de um contrato.
     *
     * @param  string  $tipo  'A' = acréscimo, 'D' = desconto
     * @return list<array<string, mixed>>
     */
    public function getServicosAdicionais(string $idContrato, string $tipo = 'A'): array
    {
        $query = QueryBuilder::for('cliente_contrato_servicos.tipo_acres_desc')
            ->query($tipo)
            ->perPage(200)
            ->sortBy('cliente_contrato_servicos.id', 'desc')
            ->filter('cliente_contrato_servicos.id_contrato', '=', $idContrato);

        return $this->list('/cliente_contrato_servicos', $query)->items;
    }
}
