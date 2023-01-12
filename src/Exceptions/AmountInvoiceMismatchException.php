<?php

namespace dnj\Invoice\Exceptions;

use dnj\Invoice\Exceptions\Contracts\JsonRender;

class AmountInvoiceMismatchException extends \Exception
{
    use JsonRender;

    public function __construct(string $message = 'amount invoice mismatch', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
