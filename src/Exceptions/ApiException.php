<?php

namespace Emitte\DfeIob\Exceptions;

use Throwable;

class ApiException extends IobException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly ?array $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
