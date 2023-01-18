<?php

namespace dnj\Invoice\Tests\Unit;

use Carbon\Carbon;
use dnj\Currency\Models\Currency;
use dnj\Invoice\Enums\InvoiceStatus;
use dnj\Invoice\Enums\PaymentStatus;
use dnj\Invoice\Exceptions\AmountInvoiceMismatchException;
use dnj\Invoice\Exceptions\CurrencyMismatchException;
use dnj\Invoice\Exceptions\FinishedInvoicePaymentsException;
use dnj\Invoice\Exceptions\InvalidInvoiceStatusException;
use dnj\Invoice\Exceptions\InvoiceUserMismatchException;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Payment;
use dnj\Invoice\Models\Product;
use dnj\Invoice\Tests\Models\User;
use dnj\Invoice\Tests\TestCase;
use dnj\Invoice\Tests\Unit\Concerns\TestingInvoice;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceManagerTest extends TestCase {
	use TestingInvoice;
	
	/**
	 * Testing create invoice.
	 */
	public function testCreateInvoice (): void {
		$now = time();
		$user = User::factory()
					->create();
		$USD = Currency::factory()
					   ->asUSD()
					   ->create();
		$invoice = $this->getInvoiceManager()
						->create($user->id , $USD->getID() , $this->products($USD) , [ 'title' => 'invoice one' ] , []);
		$this->assertSame($user->id , $invoice->user_id);
		$this->assertSame($USD->getID() , $invoice->currency_id);
		$this->assertSame($now , $invoice->getCreateTime());
	}
	
	/**
	 * Testing Delete invoice by Id.
	 */
	public function testDeleteInvoice (): void {
		$invoice = Invoice::factory()
						  ->has(Product::factory())
						  ->has(Payment::factory())
						  ->create();
		$this->getInvoiceManager()
			 ->delete($invoice->getID());
		$this->assertTrue(true);
		$invoice = Invoice::factory()
						  ->paid(Carbon::now())
						  ->create();
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->delete($invoice->getID());
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->delete(11);
	}
	
	public function testUpdateInvoice () {
		$user = User::factory()
					->create();
		$invoice = Invoice::factory()
						  ->create();
		$product1 = Product::factory()
						   ->withInvoice($invoice)
						   ->create();
		$product2 = Product::factory()
						   ->withInvoice($invoice)
						   ->create();
		Product::factory()
			   ->withInvoice($invoice)
			   ->create();
		$EUR = Currency::factory()
					   ->asEUR()
					   ->create();
		$data = [
			'title' => 'update invoice one' ,
			'user_id' => $user->id ,
			'meta' => [
				'key1' => 'value' ,
			] ,
			'currencyId' => $EUR->getID() ,
			'products' => [
				[
					'id' => $product1->id ,
					'title' => 'this is a title ' . $product1->id ,
					'price' => 125.00 ,
					'discount' => 100.00 ,
					'count' => 2 ,
					'currencyId' => $EUR->getID() ,
					'meta' => [ "key_meta" => 'value_meta' ] ,
					'distributionPlan' => [ "key1" => 'value1' ] ,
					'distribution' => [ "key" => 'value' ] ,
					'description' => "this is a test" ,
				] ,
				[
					'id' => $product2->id ,
					'title' => 'this is a title ' . $product2->id ,
					'price' => 325.00 ,
					'discount' => 0.00 ,
					'count' => 1 ,
					'currencyId' => $EUR->getID() ,
					'meta' => [ "key_meta" => 'value_meta' ] ,
					'distributionPlan' => [ "key1" => 'value1' ] ,
					'distribution' => [ "key" => 'value' ] ,
					'description' => "this is a test" ,
				] ,
				[
					'title' => 'add new product' ,
					'price' => 300.00 ,
					'discount' => 150.00 ,
					'count' => 2 ,
					'currencyId' => $EUR->getID() ,
				] ,
			] ,
		];
		$response = $this->getInvoiceManager()
						 ->update($invoice->getID() , $data);
		$this->assertSame($EUR->getID() , $response->getCurrencyId());
		$this->assertSame($data[ 'meta' ] , $response->getMeta());
		$this->assertSame($data[ 'title' ] , $response->getTitle());
		$this->assertSame($data[ 'user_id' ] , $response->getUserId());
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
	
	public function testAddProductToInvoice () {
		$invoice = Invoice::factory()
						  ->create();
		$USD = Currency::factory()
					   ->asUSD()
					   ->create();
		$data = [
			'title' => 'product3' ,
			'price' => 300 ,
			'discount' => 50.00 ,
			'currencyId' => $USD->getID() ,
			'count' => 3 ,
			'description' => 'this is a test' ,
			'distributionPlan' => [
				'key' => 'value' ,
			] ,
			'distribution' => [
				'key1' => 'value2' ,
			] ,
			'meta' => [
				'key2' => 'value2' ,
			] ,
		];
		$product = $this->getInvoiceManager()
						->addProductToInvoice($invoice->getID() , $data);
		$this->assertSame($product->getTitle() , $product[ 'title' ]);
		$this->assertSame($product->getCount() , $product[ 'count' ]);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->addProductToInvoice(11 , $data);
	}
	
	/**
	 * Testing update product.
	 */
	public function testUpdateProduct (): void {
		$product = Product::factory()
						  ->create();
		$EUR = $this->createEUR();
		$data = [
			'id' => $product->id ,
			'title' => 'product3' ,
			'price' => 300 ,
			'discount' => 50.00 ,
			'count' => 3 ,
			'currencyId' => $EUR->getID() ,
			'description' => 'this is a test' ,
			'distributionPlan' => [
				'key' => 'value' ,
			] ,
			'distribution' => [
				'key1' => 'value2' ,
			] ,
			'meta' => [
				'key2' => 'value2' ,
			] ,
		];
		$product = $this->getInvoiceManager()
						->updateProduct($product->getID() , $data);
		$this->assertSame($product->getTitle() , $product[ 'title' ]);
		$this->assertSame($product->getCount() , $product[ 'count' ]);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->updateProduct(11 , $data);
	}
	
	/**
	 * Testing delete Product.
	 */
	public function testDeleteProduct (): void {
		$product = Product::factory()
						  ->create();
		$response = $this->getInvoiceManager()
						 ->deleteProduct($product->getID());
		$this->assertSame($response->getID() , $product->invoice_id);
	}
	
	/**
	 * Testing merge invoice.
	 */
	public function testMergeInvoice (): void {
		$user = User::factory()
					->create();
		$USD = $this->createUSD();
		$first_invoice = Invoice::factory()
								->withUser($user)
								->withCurrency($USD)
								->create();
		$second_invoice = Invoice::factory()
								 ->withUser($user)
								 ->withCurrency($USD)
								 ->withMeta([ 'key1' => 'value1' ])
								 ->create();
		$data = [
			'title' => 'Merge first invoice and second invoice' ,
		];
		$invoice = $this->getInvoiceManager()
						->merge([
									$first_invoice->getID() ,
									$second_invoice->getID() ,
								] , $data);
		$this->assertSame($invoice->getTitle() , $data[ 'title' ]);
	}
	
	/**
	 * Testing merge invoice user mismatch.
	 */
	public function testMergeInvoiceUserMismatch (): void {
		$USD = $this->createUSD();
		$first_invoice = Invoice::factory()
								->withCurrency($USD)
								->create();
		$second_invoice = Invoice::factory()
								 ->withCurrency($USD)
								 ->create();
		$data = [
			'title' => 'Merge first invoice and second invoice' ,
		];
		$this->expectException(InvoiceUserMismatchException::class);
		$this->getInvoiceManager()
			 ->merge([
						 $first_invoice->getID() ,
						 $second_invoice->getID() ,
					 ] , $data);
	}
	
	/**
	 * Testing merge invoice currency mismatch.
	 */
	public function testMergeInvoiceCurrencyMismatch (): void {
		$user = User::factory()
					->create();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$first_invoice = Invoice::factory()
								->withUser($user)
								->withCurrency($EUR)
								->create();
		$second_invoice = Invoice::factory()
								 ->withUser($user)
								 ->withCurrency($USD)
								 ->create();
		$data = [
			'title' => 'Merge first invoice and second invoice' ,
		];
		$this->expectException(CurrencyMismatchException::class);
		$this->getInvoiceManager()
			 ->merge([
						 $first_invoice->getID() ,
						 $second_invoice->getID() ,
					 ] , $data);
	}
	
	/**
	 * Testing merge first invoice is paid.
	 */
	public function testMergeFirstInvoiceIsPaid (): void {
		$user = User::factory()
					->create();
		$USD = $this->createUSD();
		$first_invoice = Invoice::factory()
								->withUser($user)
								->withCurrency($USD)
								->paid(Carbon::now())
								->create();
		$second_invoice = Invoice::factory()
								 ->withUser($user)
								 ->withCurrency($USD)
								 ->create();
		$data = [
			'title' => 'Merge first invoice and second invoice' ,
		];
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->merge([
						 $first_invoice->getID() ,
						 $second_invoice->getID() ,
					 ] , $data);
	}
	
	/**
	 * Testing merge second invoice in paid.
	 */
	public function testMergeSecondInvoiceIsPaid (): void {
		$user = User::factory()
					->create();
		$USD = $this->createUSD();
		$first_invoice = Invoice::factory()
								->withUser($user)
								->withCurrency($USD)
								->create();
		$second_invoice = Invoice::factory()
								 ->withUser($user)
								 ->withCurrency($USD)
								 ->paid(Carbon::now())
								 ->create();
		$data = [
			'title' => 'Merge first invoice and second invoice' ,
		];
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->merge([
						 $first_invoice->getID() ,
						 $second_invoice->getID() ,
					 ] , $data);
	}
	
	/**
	 * Testing add payment to invoice.
	 */
	public function testAddPaymentToInvoice (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000000)
						  ->create();
		$user = User::factory()
					->create();
		Payment::factory()
			   ->withAmount(200000)
			   ->withStatus(PaymentStatus::PENDING)
			   ->withInvoice($invoice)
			   ->create();
		Payment::factory()
			   ->withAmount(200000)
			   ->withStatus(PaymentStatus::APPROVED)
			   ->withInvoice($invoice)
			   ->withEUR()
			   ->create();
		$paidAmount = Number::fromInt(200000);
		$USD = Currency::factory()
					   ->asUSD()
					   ->create();
		$payment = $this->getInvoiceManager()
						->addPaymentToInvoice($invoice->getID() , 'online' , $paidAmount , PaymentStatus::PENDING , [
							'key' => 'value' ,
						] ,                   $USD->getID());
		$this->assertSame($payment->getInvoiceID() , $invoice->getID());
		$this->expectException(InvalidInvoiceStatusException::class);
		$invoice->update([
							 'status' => InvoiceStatus::PAID ,
						 ]);
		$this->getInvoiceManager()
			 ->addPaymentToInvoice($invoice->getID() , 'online' , $invoice->getAmount() , PaymentStatus::PENDING , [
				 'key' => 'value' ,
			 ] ,                   $USD->getID());
	}
	
	/**
	 * Testing add payment to invoice Mismatch Amount
	 */
	public function testAddPaymentToInvoiceMismatchAmount (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000000)
						  ->create();
		Payment::factory()
			   ->withAmount(200000)
			   ->withStatus(PaymentStatus::PENDING)
			   ->withInvoice($invoice)
			   ->create();
		Payment::factory()
			   ->withAmount(200000)
			   ->withStatus(PaymentStatus::APPROVED)
			   ->withInvoice($invoice)
			   ->withEUR()
			   ->create();
		$paidAmount = Number::fromInt(1000000);
		$USD = Currency::factory()
					   ->asUSD()
					   ->create();
		$this->expectException(AmountInvoiceMismatchException::class);
		$this->getInvoiceManager()
			 ->addPaymentToInvoice($invoice->getID() , 'online' , $paidAmount , PaymentStatus::PENDING , [
				 'key' => 'value' ,
			 ] ,                   $USD->getID());
	}
	
	/**
	 * Testing add payment to invoice when Finished payments
	 */
	public function testAddPaymentToInvoiceFinishedPayment (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000000)
						  ->create();
		Payment::factory()
			   ->withAmount(400000)
			   ->withStatus(PaymentStatus::PENDING)
			   ->withInvoice($invoice)
			   ->create();
		Payment::factory()
			   ->withAmount(600000)
			   ->withStatus(PaymentStatus::APPROVED)
			   ->withInvoice($invoice)
			   ->withEUR()
			   ->create();
		$paidAmount = Number::fromInt(1000000);
		$USD = Currency::factory()
					   ->asUSD()
					   ->create();
		$this->expectException(FinishedInvoicePaymentsException::class);
		$this->getInvoiceManager()
			 ->addPaymentToInvoice($invoice->getID() , 'online' , $paidAmount , PaymentStatus::PENDING , [
				 'key' => 'value' ,
			 ] ,                   $USD->getID());
	}
	
	/**
	 * Testing  payment that is pending.
	 */
	public function testApprovePayment (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000)
						  ->create();
		$payment = Payment::factory()
						  ->withAmount(200)
						  ->withInvoice($invoice)
						  ->create();
		$response = $this->getInvoiceManager()
						 ->approvePayment($payment->getID());
		$this->assertSame($response->getStatus() , PaymentStatus::APPROVED);
		$payment->update([
							 'status' => PaymentStatus::APPROVED ,
						 ]);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->approvePayment(11);
	}
	
	/**
	 * Testing payment approved invalid status.
	 */
	public function testApprovePaymentInvalidStatus (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000)
						  ->create();
		$payment = Payment::factory()
						  ->withAmount(200)
						  ->withInvoice($invoice)
						  ->withStatus(PaymentStatus::APPROVED)
						  ->create();
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->approvePayment($payment->getID());
	}
	
	/**
	 * Testing payment approved with invoice paid
	 */
	public function testApprovePaymentWithInvoicePaid (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000)
						  ->create();
		Payment::factory()
			   ->withAmount(400)
			   ->withInvoice($invoice)
			   ->withStatus(PaymentStatus::APPROVED)
			   ->create();
		$payment = Payment::factory()
						  ->withAmount(600)
						  ->withInvoice($invoice)
						  ->create();
		$response = $this->getInvoiceManager()
						 ->approvePayment($payment->getID());
		$this->assertSame($response->getStatus() , PaymentStatus::APPROVED);
		$this->assertSame($response->invoice->getStatus() , InvoiceStatus::PAID);
		$this->assertSame($response->invoice->paid_at->toDateString() , Carbon::now()
																			  ->toDateString());
	}
	
	/**
	 *Testing approve payment amount mismatch.
	 */
	public function tesApprovePaymentAmountMismatch (): void {
		$invoice = Invoice::factory()
						  ->withAmount(1000)
						  ->create();
		$payment = Payment::factory()
						  ->withAmount(10000)
						  ->withInvoice($invoice)
						  ->create();
		$this->expectException(AmountInvoiceMismatchException::class);
		$this->getInvoiceManager()
			 ->approvePayment($payment->getID());
	}
	
	/**
	 * Testing reject payment.
	 */
	public function testRejectPayment (): void {
		$invoice = Invoice::factory()
						  ->create();
		$payment = Payment::factory()
						  ->withInvoice($invoice)
						  ->create();
		$response = $this->getInvoiceManager()
						 ->rejectPayment($payment->getID());
		$this->assertSame($response->getStatus() , PaymentStatus::REJECTED);
		$payment->update([
							 'status' => PaymentStatus::APPROVED ,
						 ]);
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->rejectPayment($payment->getID());
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->rejectPayment(11);
	}
}
