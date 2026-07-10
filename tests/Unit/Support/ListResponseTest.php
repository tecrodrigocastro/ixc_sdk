<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Support\ListResponse;

final class ListResponseTest extends TestCase
{
    public function test_normalizes_total_and_registros(): void
    {
        $response = ListResponse::fromArray([
            'total' => '2',
            'registros' => [['id' => 1], ['id' => 2]],
        ]);

        $this->assertSame(2, $response->total);
        $this->assertCount(2, $response);
        $this->assertSame(['id' => 1], $response->first());
        $this->assertFalse($response->isEmpty());
    }

    public function test_defaults_to_empty_when_registros_is_missing(): void
    {
        $response = ListResponse::fromArray(['type' => 'success']);

        $this->assertSame(0, $response->total);
        $this->assertTrue($response->isEmpty());
        $this->assertNull($response->first());
    }

    public function test_is_iterable(): void
    {
        $response = ListResponse::fromArray([
            'registros' => [['id' => 1], ['id' => 2], ['id' => 3]],
        ]);

        $ids = [];
        foreach ($response as $item) {
            $ids[] = $item['id'];
        }

        $this->assertSame([1, 2, 3], $ids);
    }
}
