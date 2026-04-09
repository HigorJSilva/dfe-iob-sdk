<?php

namespace Emitte\DfeIob\Auth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Armazenamento de tokens usando o cache do Laravel.
 * Persiste tokens entre requisições (ex: cache em Redis ou file).
 */
class LaravelTokenStore implements TokenStore
{
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->cache->put($key, $value, $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }
}
