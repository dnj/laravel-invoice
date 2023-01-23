<?php

namespace dnj\Invoice\Exceptions;

use dnj\Invoice\Exceptions\Contracts\JsonRender;

class CurrencyMismatchException extends \Exception
{
    use JsonRender;

    public function __construct(string $message = 'currency mismatch', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
