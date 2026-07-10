<?php

namespace RedRodrigo\IxcSdk\Support;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Normaliza o formato heterogêneo de resposta de listagem do IXC
 * (`['total' => ..., 'registros' => [...]]`, às vezes sem `total`,
 * às vezes sem `registros`) em um objeto único e previsível.
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final class ListResponse implements Countable, IteratorAggregate
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function __construct(
        public readonly int $total,
        public readonly array $items,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  Corpo bruto retornado por HttpClientInterface::get()
     */
    public static function fromArray(array $data): self
    {
        $items = $data['registros'] ?? [];

        return new self(
            total: (int) ($data['total'] ?? count($items)),
            items: $items,
        );
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
