<?php

namespace dnj\Invoice\Exceptions;

use dnj\Invoice\Exceptions\Contracts\JsonRender;
use Exception;
class InvoiceUserMismatchException extends Exception {
	use JsonRender;
	
	public function __construct ( string $message = "Invoice user mismatch" , int $code = 0 , ?\Throwable $previous = null ) {
		parent::__construct($message , $code , $previous);
	}
}