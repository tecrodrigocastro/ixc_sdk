<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Estoque e almoxarifado — IXC Soft.
 *
 * Fluxo típico de consulta de estoque de um técnico:
 *   1. getAlmoxarifadoByTecnico($idUsuario) → obtém o id_almoxarifado do técnico
 *   2. getEstoqueByAlmoxarifado($idAlmox)   → lista os produtos com saldo
 *   3. getProdutoById($idProduto)           → detalhes de um produto específico
 *
 * Endpoints cobertos:
 *   GET /almox_usuario           — relação usuário → almoxarifado
 *   GET /view_prod_estoque_almox — view de saldo por almoxarifado (somente saldo > 0)
 *   GET /produtos                — catálogo de produtos
 *   GET /almox                   — cadastro de almoxarifados
 *   GET /transf_almox_top        — transferências entre almoxarifados (cabeçalho)
 *   GET /transf_almox_item       — itens de uma transferência entre almoxarifados
 *
 * Validado contra a API real da Orbe em 2026-07-25: `/transf_almox_top`
 * está bloqueado (resposta HTML de erro, não JSON) para o usuário de API da
 * Orbe — não usar getTransferenciasByPeriodo()/getItensTransferencia() com
 * um ID vindo dele em produção. `/transf_almox_item` (sem filtro de
 * cabeçalho) FUNCIONA normalmente (456k+ registros, campos confirmados: id,
 * id_produto, qtde, id_transf_almox, id_patrimonio, id_unidade,
 * fator_conversao, unidade_sigla, id_requisicao_material_item — não tem
 * campo de data próprio) — use getAllItensTransferencia() pra coletar como
 * snapshot paginado por ID em vez de por período/cabeçalho.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class EstoqueResource extends AbstractResource
{
    /**
     * Almoxarifados vinculados a um técnico/usuário.
     *
     * @return list<array<string, mixed>>
     */
    public function getAlmoxarifadoByTecnico(string $idUsuario): array
    {
        $query = QueryBuilder::for('almox_usuario.id_usuario')
            ->query($idUsuario)
            ->perPage(20)
            ->sortBy('almox_usuario.id', 'desc');

        return $this->list('/almox_usuario', $query)->items;
    }

    /**
     * Todos os produtos com saldo positivo em um almoxarifado.
     *
     * @return array<string, mixed>
     */
    public function getEstoqueByAlmoxarifado(string $idAlmoxarifado): array
    {
        $query = QueryBuilder::for('view_prod_estoque_almox.almox_id')
            ->query($idAlmoxarifado)
            ->perPage(300)
            ->sortBy('view_prod_estoque_almox.almox_id', 'desc')
            ->filter('view_prod_estoque_almox.saldo', '>', '0.0');

        $dados = $this->query('/view_prod_estoque_almox', $query);

        if (empty($dados['total']) || $dados['total'] == 0) {
            return [];
        }

        return $dados;
    }

    /**
     * Dados de um produto pelo ID.
     *
     * @return list<array<string, mixed>>
     */
    public function getProdutoById(string $idProduto): array
    {
        $query = QueryBuilder::for('produtos.id')
            ->query($idProduto)
            ->perPage(300)
            ->sortBy('produtos.id', 'desc');

        return $this->list('/produtos', $query)->items;
    }

    /**
     * Todos os almoxarifados cadastrados.
     *
     * @return list<array<string, mixed>>
     */
    public function getAllAlmoxarifados(int $perPage = 1000): array
    {
        $query = QueryBuilder::for('almox.id')
            ->query(0)
            ->operator('>=')
            ->perPage($perPage)
            ->sortBy('almox.id', 'desc');

        return $this->list('/almox', $query)->items;
    }

    /**
     * Transferências entre almoxarifados em um período (cabeçalho — origem,
     * destino, data). Consulte getItensTransferencia() para os produtos
     * movimentados em cada uma.
     *
     * @return list<array<string, mixed>>
     */
    public function getTransferenciasByPeriodo(string $dataInicial, string $dataFinal): array
    {
        $query = QueryBuilder::for('transf_almox_top.data')
            ->perPage(1000)
            ->sortBy('transf_almox_top.id', 'desc')
            ->filter('transf_almox_top.data', '>=', $dataInicial)
            ->filter('transf_almox_top.data', '<=', $dataFinal);

        return $this->list('/transf_almox_top', $query)->items;
    }

    /**
     * Produtos/patrimônios movimentados em uma transferência entre almoxarifados.
     *
     * Requer o id da transferência (transf_almox_top) — como esse endpoint
     * está bloqueado na Orbe, prefira getAllItensTransferencia() quando não
     * tiver esse ID disponível de outra fonte.
     *
     * @return list<array<string, mixed>>
     */
    public function getItensTransferencia(string $idTransferencia): array
    {
        $query = QueryBuilder::for('transf_almox_item.id_transf_almox')
            ->query($idTransferencia)
            ->perPage(500)
            ->sortBy('transf_almox_item.id', 'desc');

        return $this->list('/transf_almox_item', $query)->items;
    }

    /**
     * Lista paginada de todos os itens de transferência entre almoxarifados,
     * mais recente primeiro. Sem filtro de período — `transf_almox_item` não
     * tem campo de data próprio (a data fica só no cabeçalho `transf_almox_top`,
     * que está bloqueado na Orbe). Colete como snapshot incremental por ID.
     *
     * @return list<array<string, mixed>>
     */
    public function getAllItensTransferencia(int $page = 1, int $perPage = 500): array
    {
        $query = QueryBuilder::for('transf_almox_item.id')
            ->query(0)
            ->operator('>=')
            ->page($page)
            ->perPage($perPage)
            ->sortBy('transf_almox_item.id', 'desc');

        return $this->list('/transf_almox_item', $query)->items;
    }
}
