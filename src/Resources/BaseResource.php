<?php

namespace Emitte\DfeIob\Resources;

use Emitte\DfeIob\Http\HttpClient;

abstract class BaseResource
{
    public function __construct(protected readonly HttpClient $client)
    {
    }
}
