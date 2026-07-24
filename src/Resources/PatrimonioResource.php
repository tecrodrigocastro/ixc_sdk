<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Patrimônio — IXC Soft.
 *
 * Endpoint coberto (somente leitura):
 *   GET /patrimonio — cadastro de equipamentos tombados como patrimônio
 *
 * Validado contra a API real da Orbe em 2026-07-23: acessível, 3.545
 * registros retornados.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class PatrimonioResource extends AbstractResource
{
    /**
     * Um patrimônio pelo ID.
     *
     * @return array<string, mixed>
     */
    public function getPatrimonioById(string $idPatrimonio): array
    {
        $query = QueryBuilder::for('patrimonio.id')
            ->query($idPatrimonio)
            ->perPage(1)
            ->sortBy('patrimonio.id', 'desc');

        return $this->list('/patrimonio', $query)->first() ?? [];
    }

    /**
     * Lista paginada de todos os patrimônios cadastrados (mais recente primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getAllPatrimonios(int $page = 1, int $perPage = 500): array
    {
        $query = QueryBuilder::for('patrimonio.id')
            ->query(0)
            ->operator('>=')
            ->page($page)
            ->perPage($perPage)
            ->sortBy('patrimonio.id', 'desc');

        return $this->list('/patrimonio', $query)->items;
    }
}
