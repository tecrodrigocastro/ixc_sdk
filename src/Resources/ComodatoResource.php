<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Comodato de equipamentos — IXC Soft.
 *
 * Endpoints cobertos (somente leitura):
 *   GET /cliente_contrato_comodato   — produto/patrimônio em comodato vinculado a um contrato
 *   GET /su_oss_mov_comodato_wiz     — comodato lançado dentro de uma OS específica
 *
 * `status_comodato` (tabela `movimento_produtos`, base de ambos os
 * endpoints): 'E' = Entregue (em posse do cliente) | outros valores indicam
 * devolvido/baixado — ver `ComodatoResource::getComodatoPendenteByOs()`.
 *
 * Nem todo provedor libera esses dois endpoints pro usuário de API — em
 * testes reais, ambos retornaram uma página HTML de erro (não JSON) em vez
 * de dado. Se isso acontecer, valide com o suporte do IXC Soft se o plano/
 * usuário tem acesso liberado antes de depender deles em produção.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class ComodatoResource extends AbstractResource
{
    /**
     * Todos os itens de comodato (produto ou patrimônio) vinculados a um contrato.
     *
     * @return list<array<string, mixed>>
     */
    public function getComodatosByContrato(string $idContrato): array
    {
        $query = QueryBuilder::for('cliente_contrato_comodato.id_contrato')
            ->query($idContrato)
            ->perPage(200)
            ->sortBy('cliente_contrato_comodato.id', 'desc');

        return $this->list('/cliente_contrato_comodato', $query)->items;
    }

    /**
     * Comodato entregue (status_comodato = 'E') ainda em aberto em uma OS —
     * útil pra cruzar saída de estoque × comodato do cliente × declaração do
     * técnico numa auditoria de equipamento.
     *
     * @return list<array<string, mixed>>
     */
    public function getComodatoPendenteByOs(string $idOs): array
    {
        $query = QueryBuilder::for('movimento_produtos.id_oss_chamado')
            ->query($idOs)
            ->perPage(200)
            ->sortBy('movimento_produtos.id', 'desc')
            ->filter('movimento_produtos.status_comodato', '=', 'E');

        return $this->list('/su_oss_mov_comodato_wiz', $query)->items;
    }
}
