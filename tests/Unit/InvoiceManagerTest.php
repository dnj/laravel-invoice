<?php

namespace dnj\Invoice\Tests\Unit;
use dnj\Invoice\Tests\TestCase;

class InvoiceManagerTest extends TestCase {
	
	
	public function test_create_invoice()
	{
		$USD = $this->createUSD();
		dd($this->createUser());
	}
}