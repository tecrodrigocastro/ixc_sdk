<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Entrada de estoque e compras — IXC Soft.
 *
 * Endpoints cobertos (somente leitura):
 *   GET /entrada               — notas de entrada/compra
 *   GET /requisicao_compra     — requisição de compra (cabeçalho)
 *   GET /requisicao_compra_itens — itens da requisição de compra
 *   GET /pedido_compra         — pedido de compra (cabeçalho)
 *   GET /pedido_compra_itens   — itens do pedido de compra
 *
 * `/entrada` não tem endpoint de itens/produtos — é só o cabeçalho da nota
 * fiscal (fornecedor, valor total, datas). Pra saber quais produtos entraram,
 * use `getItensPedidoCompra()`/`getItensRequisicaoCompra()`.
 *
 * O campo de data de `/entrada` não foi confirmado contra a documentação —
 * getEntradas() lista por ID (mais recente primeiro) em vez de filtrar por
 * período; valide o nome exato do campo de data antes de trocar por um
 * filtro de intervalo.
 *
 * Em testes reais, `/requisicao_compra_itens` retornou uma página HTML de
 * erro (não JSON) pro usuário de API usado, enquanto `/pedido_compra_itens`
 * funcionou normalmente — nem todo par "cabeçalho + itens" do IXC vem
 * necessariamente liberado igual; não assuma que um funcionando implica o
 * outro também funcionar, valide os dois separadamente no seu provedor.
 *
 * `id_requisicao_compra`/`id_pedido_compra` nos itens seguem a mesma
 * convenção de nomenclatura usada em `requisicao_material_item.id_requisicao`
 * e `transf_almox_item.id_transf_almox` — não confirmados byte a byte no
 * Postman (o exemplo de listagem usa `.id` genérico), validar no primeiro uso real.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class EntradaEstoqueResource extends AbstractResource
{
    /**
     * Lista paginada de notas de entrada, mais recente primeiro.
     *
     * @return list<array<string, mixed>>
     */
    public function getEntradas(int $page = 1, int $perPage = 500): array
    {
        $query = QueryBuilder::for('entrada.id')
            ->query(0)
            ->operator('>=')
            ->page($page)
            ->perPage($perPage)
            ->sortBy('entrada.id', 'desc');

        return $this->list('/entrada', $query)->items;
    }

    /**
     * Lista paginada de requisições de compra, mais recente primeiro.
     *
     * @return list<array<string, mixed>>
     */
    public function getRequisicoesCompra(int $page = 1, int $perPage = 500): array
    {
        $query = QueryBuilder::for('requisicao_compra.id')
            ->query(0)
            ->operator('>=')
            ->page($page)
            ->perPage($perPage)
            ->sortBy('requisicao_compra.id', 'desc');

        return $this->list('/requisicao_compra', $query)->items;
    }

    /**
     * Itens de uma requisição de compra.
     *
     * @return list<array<string, mixed>>
     */
    public function getItensRequisicaoCompra(string $idRequisicaoCompra): array
    {
        $query = QueryBuilder::for('requisicao_compra_itens.id_requisicao_compra')
            ->query($idRequisicaoCompra)
            ->perPage(500)
            ->sortBy('requisicao_compra_itens.id', 'desc');

        return $this->list('/requisicao_compra_itens', $query)->items;
    }

    /**
     * Lista paginada de pedidos de compra, mais recente primeiro.
     *
     * @return list<array<string, mixed>>
     */
    public function getPedidosCompra(int $page = 1, int $perPage = 500): array
    {
        $query = QueryBuilder::for('pedido_compra.id')
            ->query(0)
            ->operator('>=')
            ->page($page)
            ->perPage($perPage)
            ->sortBy('pedido_compra.id', 'desc');

        return $this->list('/pedido_compra', $query)->items;
    }

    /**
     * Itens de um pedido de compra.
     *
     * @return list<array<string, mixed>>
     */
    public function getItensPedidoCompra(string $idPedidoCompra): array
    {
        $query = QueryBuilder::for('pedido_compra_itens.id_pedido_compra')
            ->query($idPedidoCompra)
            ->perPage(500)
            ->sortBy('pedido_compra_itens.id', 'desc');

        return $this->list('/pedido_compra_itens', $query)->items;
    }
}
