<?php

namespace Emitte\DfeIob\Auth;

interface TokenStore
{
    public function get(string $key): mixed;

    public function put(string $key, mixed $value, int $ttlSeconds): void;

    public function forget(string $key): void;
}
