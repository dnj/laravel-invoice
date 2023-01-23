<?php

namespace dnj\Invoice\Exceptions;

use dnj\Invoice\Exceptions\Contracts\JsonRender;

class InvalidInvoiceStatusException extends \Exception
{
    use JsonRender;

    public function __construct(string $message = 'Invalid status', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
