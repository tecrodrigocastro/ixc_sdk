<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Contratos de venda e serviços adicionais — IXC Soft.
 *
 * Endpoints cobertos:
 *   GET /vd_contratos — planos/contratos de venda
 *   GET /tv_usuarios  — dados de TV por contrato
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
}
