<?php

namespace Emitte\DfeIob\Auth;

/**
 * Armazenamento de tokens em memória (sem persistência entre requisições).
 * Útil para scripts CLI ou quando o Laravel Cache não estiver disponível.
 */
class InMemoryTokenStore implements TokenStore
{
    /** @var array<string, array{value: mixed, expires_at: int}> */
    private array $store = [];

    public function get(string $key): mixed
    {
        if (!isset($this->store[$key])) {
            return null;
        }

        if (time() >= $this->store[$key]['expires_at']) {
            unset($this->store[$key]);
            return null;
        }

        return $this->store[$key]['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->store[$key] = [
            'value'      => $value,
            'expires_at' => time() + $ttlSeconds,
        ];
    }

    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }
}
