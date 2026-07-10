<?php

namespace RedRodrigo\IxcSdk\Tests\Support;

use Psr\SimpleCache\CacheInterface;

/**
 * Implementação PSR-16 em memória, usada só nos testes de CachingHttpClient.
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $storage = [];

    public int $setCalls = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->setCalls++;
        $this->storage[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->storage[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->storage);
    }
}
