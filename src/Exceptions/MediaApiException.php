<?php

namespace TCG\Voyager\Exceptions;

use RuntimeException;

class MediaApiException extends RuntimeException
{
    public function __construct(
        public readonly string $apiCode,
        string $message,
        public readonly int $statusCode = 400,
        public readonly array $extra = []
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $code, string $message = 'Invalid request', array $extra = []): self
    {
        return new self($code, $message, 400, $extra);
    }
}
