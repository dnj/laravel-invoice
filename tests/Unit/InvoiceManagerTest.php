<?php

namespace dnj\Invoice\Tests\Unit;

use dnj\Invoice\Contracts\InvoiceStatus;
use dnj\Invoice\Contracts\PaymentStatus;
use dnj\Invoice\Exceptions\CurrencyMismatchException;
use dnj\Invoice\Exceptions\InvalidInvoiceStatusException;
use dnj\Invoice\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceManagerTest extends TestCase {
	public function test_CreateInvoice () {
		$now = time();
		$user = $this->createUser();
		$USD = $this->createUSD();
		$invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$this->assertSame($user->id , $invoice->user_id);
		$this->assertSame($USD->getID() , $invoice->currency_id);
		$this->assertSame($now , $invoice->getCreateTime());
	}
	
	public function test_DeleteInvoice () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$invoice_unpaid = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$invoice_paid = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice two' ]);
		$invoice_paid->update([
								  'status' => InvoiceStatus::PAID->value ,
							  ]);
		$this->getInvoiceManager()
			 ->delete($invoice_unpaid->getID());
		$this->assertTrue(true);
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->delete($invoice_paid->id);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->delete(11);
	}
	
	public function test_UpdateInvoice () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$data = [
			'title' => "update invoice one" ,
			'meta' => [
				'key1' => "value" ,
			] ,
			'currencyId' => $EUR->getID() ,
			'products' => [
				[
					'title' => 'product3' ,
					'price' => 100.00 ,
					'count' => 2 ,
				] ,
			] ,
		];
		$invoice = $this->getInvoiceManager()
						->update($invoice->getID() , $data);
		$this->assertSame($EUR->getID() , $invoice->getCurrencyId());
		$this->assertSame($data[ 'meta' ] , $invoice->getMeta());
		$this->assertSame($data[ 'title' ] , $invoice->getTitle());
		$invoice->update([
							 'status' => InvoiceStatus::PAID->value ,
						 ]);
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->update($invoice->getID() , $data);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->update(11 , $data);
	}
	
	public function test_AddProductToInvoice () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$product = [
			'title' => 'product3' ,
			'price' => 100.00 ,
			'discount' => 50.00 ,
			'count' => 2 ,
		];
		$product = $this->getInvoiceManager()
						->addProductToInvoice($invoice->getID() , $product);
		$this->assertSame($product->getTitle() , $product[ 'title' ]);
		$this->assertSame($product->getCount() , $product[ 'count' ]);
	}
	
	public function test_UpdateProduct () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$data = [
			'title' => 'product3' ,
			'price' => 300 ,
			'discount' => 50.00 ,
			'count' => 3 ,
			'description' => "this is a test" ,
			'distributionPlan' => [
				'key' => "value" ,
			] ,
		];
		$product = $this->getInvoiceManager()
						->updateProduct(1 , $data);
		$this->assertSame($product->getTitle() , $product[ 'title' ]);
		$this->assertSame($product->getCount() , $product[ 'count' ]);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->updateProduct(11 , $data);
	}
	
	public function test_DeleteProduct () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$response = $this->getInvoiceManager()
						 ->deleteProduct(1);
		$this->assertSame($invoice->getID() , $response->getID());
		$this->assertSame($invoice->getTitle() , $response->getTitle());
		$this->assertSame($invoice->getCurrencyId() , $response->getCurrencyId());
	}
	
	public function test_merge () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$first_invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$second_invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$data = [
			'title' => 'Merge first invoice and second invoice' ,
		];
		$invoice = $this->getInvoiceManager()
						->merge([
									$first_invoice->getID() ,
									$second_invoice->getID() ,
								] , $data);
		$this->assertSame($invoice->getTitle() , $data[ 'title' ]);
		$first_invoice->update([
								   'currency_id' => $EUR->getID() ,
							   ]);
		$this->expectException(CurrencyMismatchException::class);
		$this->getInvoiceManager()
			 ->merge([
						 $first_invoice->getID() ,
						 $second_invoice->getID() ,
					 ] , $data);
		$second_invoice->updata([
									'status' => InvoiceStatus::PAID ,
								]);
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->merge([
						 $first_invoice->getID() ,
						 $second_invoice->getID() ,
					 ] , $data);
	}
	
	public function test_addPaymentToInvoice () {
		$user = $this->createUser();
		$USD = $this->createUSD();
		$invoice = $this->createInvoice($user->id , $USD->getID() , $this->products() , [ 'title' => 'invoice one' ]);
		$payment = $this->getInvoiceManager()
						->addPaymentToInvoice($invoice->getID() , 'online' , $invoice->getAmount() , PaymentStatus::PENDING , [
							'key' => 'value',
						]);
		$this->assertSame($payment->getInvoiceID() , $invoice->getID());
	}
	
	public function test_ApprovePayment () {
		$payment = $this->createPaymentInvioce();
		$payment = $this->getInvoiceManager()
						->approvePayment($payment->getID());
		$this->assertSame($payment->getStatus() , PaymentStatus::APPROVED);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			->approvePayment(11);
	}
	
	public function test_RejectPayment () {
		$payment = $this->createPaymentInvioce();
		$payment = $this->getInvoiceManager()
						->rejectPayment($payment->getID());
		$this->assertSame($payment->getStatus() , PaymentStatus::REJECTED);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->approvePayment(11);
	}
}