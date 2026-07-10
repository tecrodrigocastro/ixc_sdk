<?php

namespace RedRodrigo\IxcSdk\Query;

/**
 * Builder fluente para o protocolo de listagem do IXC Soft
 * (`qtype`/`query`/`oper`/`page`/`rp`/`sortname`/`sortorder`/`grid_param`).
 *
 * O IXC exige um filtro "primário" (qtype/query/oper) em toda consulta,
 * mesmo quando ele não é usado de fato — nesses casos é comum passar
 * uma query vazia e colocar os filtros reais em `filter()` (grid_param).
 *
 * @example
 * QueryBuilder::for('cliente.id')
 *     ->query($id)
 *     ->operator('=')
 *     ->perPage(1)
 *     ->toArray();
 *
 * @example Vários filtros combinados via grid_param (AND implícito)
 * QueryBuilder::for('cliente_contrato.id_vendedor')
 *     ->perPage(400)
 *     ->filter('cliente_contrato.status', '=', 'A')
 *     ->filter('cliente_contrato.data_ativacao', '>=', $start)
 *     ->filter('cliente_contrato.data_ativacao', '<=', $end)
 *     ->toArray();
 */
final class QueryBuilder
{
    /** @var list<array{TB: string, OP: string, P: string|int}> */
    private array $filters = [];

    private string|int $queryValue = '';

    private string $operator = '=';

    private int $page = 1;

    private int $rp = 200;

    private ?string $sortName = null;

    private string $sortOrder = 'desc';

    private function __construct(private readonly string $qtype)
    {
    }

    public static function for(string $qtype): self
    {
        return new self($qtype);
    }

    public function query(string|int $value): self
    {
        $this->queryValue = $value;

        return $this;
    }

    public function operator(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function filter(string $field, string $operator, string|int $value): self
    {
        $this->filters[] = ['TB' => $field, 'OP' => $operator, 'P' => $value];

        return $this;
    }

    public function page(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function perPage(int $rp): self
    {
        $this->rp = $rp;

        return $this;
    }

    public function sortBy(string $field, string $direction = 'desc'): self
    {
        $this->sortName = $field;
        $this->sortOrder = $direction;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [
            'qtype' => $this->qtype,
            'query' => $this->queryValue,
            'oper' => $this->operator,
            'page' => (string) $this->page,
            'rp' => (string) $this->rp,
            'sortname' => $this->sortName ?? $this->qtype,
            'sortorder' => $this->sortOrder,
        ];

        if ($this->filters !== []) {
            $params['grid_param'] = json_encode($this->filters, JSON_UNESCAPED_UNICODE);
        }

        return $params;
    }
}
