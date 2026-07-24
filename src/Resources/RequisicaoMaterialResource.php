<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Requisição de materiais e devolução — IXC Soft.
 *
 * Endpoints cobertos (somente leitura):
 *   GET /requisicao_material               — requisição (cabeçalho: técnico, almoxarifado, data)
 *   GET /requisicao_material_item          — itens da requisição (produto, quantidade)
 *   GET /requisicao_devolucao_material     — devolução/transferência com confirmação
 *   GET /itens_requisicao_devolucao_material — itens da devolução
 *
 * Validado contra a API real da Orbe em 2026-07-23: `/requisicao_material` e
 * `/requisicao_material_item` acessíveis com dado real (253k+ itens);
 * `/requisicao_devolucao_material` retornou "usuário não possui um
 * almoxarifado padrão" — falta de configuração no cadastro do usuário da API
 * no IXC, não bloqueio de acesso.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class RequisicaoMaterialResource extends AbstractResource
{
    /**
     * Requisições de material abertas por um técnico em um período.
     *
     * @return list<array<string, mixed>>
     */
    public function getRequisicoesByTecnico(string $idTecnico, string $dataInicial, string $dataFinal): array
    {
        $query = QueryBuilder::for('requisicao_material.id_tecnico')
            ->query($idTecnico)
            ->perPage(500)
            ->sortBy('requisicao_material.data', 'desc')
            ->filter('requisicao_material.data', '>=', $dataInicial)
            ->filter('requisicao_material.data', '<=', $dataFinal);

        return $this->list('/requisicao_material', $query)->items;
    }

    /**
     * Todas as requisições de material em um período (todos os técnicos).
     *
     * @return list<array<string, mixed>>
     */
    public function getRequisicoesByPeriodo(string $dataInicial, string $dataFinal): array
    {
        $query = QueryBuilder::for('requisicao_material.data')
            ->perPage(2000)
            ->sortBy('requisicao_material.data', 'desc')
            ->filter('requisicao_material.data', '>=', $dataInicial)
            ->filter('requisicao_material.data', '<=', $dataFinal);

        return $this->list('/requisicao_material', $query)->items;
    }

    /**
     * Itens (produtos + quantidade) de uma requisição de material.
     *
     * @return list<array<string, mixed>>
     */
    public function getItensRequisicao(string $idRequisicao): array
    {
        $query = QueryBuilder::for('requisicao_material_item.id_requisicao')
            ->query($idRequisicao)
            ->perPage(500)
            ->sortBy('requisicao_material_item.id', 'desc');

        return $this->list('/requisicao_material_item', $query)->items;
    }

    /**
     * Devoluções/transferências com confirmação registradas em um período.
     *
     * @return list<array<string, mixed>>
     */
    public function getDevolucoesByPeriodo(string $dataInicial, string $dataFinal): array
    {
        $query = QueryBuilder::for('requisicao_devolucao_material.data')
            ->perPage(2000)
            ->sortBy('requisicao_devolucao_material.data', 'desc')
            ->filter('requisicao_devolucao_material.data', '>=', $dataInicial)
            ->filter('requisicao_devolucao_material.data', '<=', $dataFinal);

        return $this->list('/requisicao_devolucao_material', $query)->items;
    }

    /**
     * Itens (produto/patrimônio + quantidade) de uma devolução.
     *
     * @return list<array<string, mixed>>
     */
    public function getItensDevolucao(string $idDevolucao): array
    {
        $query = QueryBuilder::for('itens_requisicao_devolucao_material.id_requisicao_devolucao_material')
            ->query($idDevolucao)
            ->perPage(500)
            ->sortBy('itens_requisicao_devolucao_material.id', 'desc');

        return $this->list('/itens_requisicao_devolucao_material', $query)->items;
    }
}
