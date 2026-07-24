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
 * Bloqueados (resposta HTML de erro, não JSON) para o usuário de API da
 * Orbe em 2026-07-23 — validar com o suporte IXC Soft antes de depender
 * deles em produção (afeta diretamente o RF-09 do Auditor Orbe).
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
     * usado na verificação em 3 pontas do RF-09 (saída de estoque × comodato
     * do cliente × declaração do técnico).
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
