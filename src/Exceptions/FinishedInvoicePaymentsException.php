<?php

namespace dnj\Invoice\Exceptions;

use dnj\Invoice\Exceptions\Contracts\JsonRender;

class FinishedInvoicePaymentsException extends \Exception
{
    use JsonRender;

    public function __construct(string $message = 'finished invoice payments ', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
