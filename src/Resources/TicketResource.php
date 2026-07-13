<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Tickets/protocolos de atendimento (su_ticket) — IXC Soft.
 *
 * Endpoint coberto:
 *   GET /su_ticket — tickets de atendimento
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class TicketResource extends AbstractResource
{
    /**
     * Tickets criados hoje.
     *
     * @return array<string, mixed>
     */
    public function getAtendimentos(): array
    {
        $hoje = date('Y-m-d');

        $query = QueryBuilder::for('su_ticket.id')
            ->perPage(2000)
            ->sortBy('su_ticket.id', 'desc')
            ->filter('su_ticket.data_criacao', 'L', $hoje);

        return $this->query('/su_ticket', $query);
    }

    /**
     * Tickets criados em um período.
     *
     * @return list<array<string, mixed>>
     */
    public function getTicketsByPeriod(string $dataInicial, string $dataFinal): array
    {
        $query = QueryBuilder::for('su_ticket.id')
            ->perPage(2000)
            ->sortBy('su_ticket.id', 'desc')
            ->filter('su_ticket.data_criacao', 'GE', $dataInicial)
            ->filter('su_ticket.data_criacao', 'LE', $dataFinal);

        return $this->list('/su_ticket', $query)->items;
    }
}
