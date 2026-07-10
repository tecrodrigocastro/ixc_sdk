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
}
