<?php

namespace dnj\Invoice\Tests\Unit;

use dnj\Invoice\Contracts\InvoiceStatus;
use dnj\Invoice\Contracts\PaymentStatus;
use dnj\Invoice\Exceptions\AmountInvoiceMismatchException;
use dnj\Invoice\Exceptions\CurrencyMismatchException;
use dnj\Invoice\Exceptions\InvalidInvoiceStatusException;
use dnj\Invoice\Exceptions\InvoiceUserMismatchException;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Payment;
use dnj\Invoice\Models\Product;
use dnj\Invoice\Tests\Models\User;
use dnj\Invoice\Tests\TestCase;
use dnj\Invoice\Tests\Unit\Concerns\TestingInvoice;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceManagerTest extends TestCase {
	use TestingInvoice;
	
	/**
	 * Testing create invoice
	 *
	 * @return void
	 */
	public function test_createInvoice (): void {
		$now = time();
		$user = User::factory()
					->create();
		$USD = $this->createUSD();
		$invoice = $this->getInvoiceManager()
						->create($user->id , $USD->getID() , $this->products($USD) , [ 'title' => 'invoice one' ] , []);
		$this->assertSame($user->id , $invoice->user_id);
		$this->assertSame($USD->getID() , $invoice->currency_id);
		$this->assertSame($now , $invoice->getCreateTime());
	}
	
	/**
	 * Testing Delete invoice by Id
	 *
	 * @return void
	 */
	public function test_deleteInvoice (): void {
		
		$invoice = Invoice::factory()
						  ->create();
		$this->getInvoiceManager()
			 ->delete($invoice->getID());
		$this->assertTrue(true);
		$invoice = Invoice::factory()
						  ->paid()
						  ->create();
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->delete($invoice->getID());
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->delete(11);
	}
	
	public function test_updateInvoice () {
		$invoice = Invoice::factory()
						  ->has(Product::factory(3) , 'products')
						  ->create();
		$EUR = $this->createEUR();
		$data = [
			'title' => "update invoice one" ,
			'meta' => [
				'key1' => "value" ,
			] ,
			'currencyId' => $EUR->getID() ,
			'products' => [
				[
					'id' => 1 ,
					'price' => 125.00 ,
					'discount' => 100.00 ,
					'count' => 2 ,
				] ,
				[
					'id' => 2 ,
					'price' => 325.00 ,
					'discount' => 0.00 ,
					'count' => 1 ,
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
	
	/**
	 * Testing update product
	 * @return void
	 */
	public function test_updateProduct (): void {
		$product = Product::factory()
						  ->create();
		$data = [
			'id' => 1 ,
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
						->updateProduct($product->getID() , $data);
		$this->assertSame($product->getTitle() , $product[ 'title' ]);
		$this->assertSame($product->getCount() , $product[ 'count' ]);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->updateProduct(11 , $data);
	}
	
	/**
	 * Testing delete Product
	 * @return void
	 */
	public function test_deleteProduct (): void {
		$product = Product::factory()
						  ->create();
		$response = $this->getInvoiceManager()
						 ->deleteProduct($product->getID());
		$this->assertSame($response->getID() , $product->invoice_id);
	}
	
	/**
	 * Testing merge invoice
	 * @return void
	 */
	public function test_merge_invoice (): void {
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
	 * Testing merge invoice user mismatch
	 * @return void
	 */
	public function test_merge_invoice_user_mismatch (): void {
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
	 * Testing merge invoice currency mismatch
	 * @return void
	 */
	public function test_merge_invoice_currency_mismatch (): void{
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
	 * Testing merge first invoice is paid
	 * @return void
	 */
	public function test_merge_first_invoice_is_paid ():void {
		$user = User::factory()
					->create();
		$USD = $this->createUSD();
		$first_invoice = Invoice::factory()
								->withUser($user)
								->withCurrency($USD)
								->paid()
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
	 * Testing merge second invoice in paid
	 * @return void
	 */
	public function test_merge_second_invoice_is_paid (): void {
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
								 ->paid()
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
	 * Testing add payment to invoice
	 * @return void
	 */
	public function tes_addPaymentToInvoice ():void {
		$invoice = Invoice::factory()
						  ->withAmount(10000)
						  ->create();
		$payment = $this->getInvoiceManager()
						->addPaymentToInvoice($invoice->getID() , 'online' , $invoice->getAmount() , PaymentStatus::PENDING , [
							'key' => 'value' ,
						]);
		$this->assertSame($payment->getInvoiceID() , $invoice->getID());
		$this->expectException(InvalidInvoiceStatusException::class);
		$invoice->update([
							 'status' => InvoiceStatus::PAID ,
						 ]);
		$this->getInvoiceManager()
			 ->addPaymentToInvoice($invoice->getID() , 'online' , $invoice->getAmount() , PaymentStatus::PENDING , [
				 'key' => 'value' ,
			 ]);
	}
	
	/**
	 * Testing approve payment
	 * @return void
	 */
	public function test_approvePayment (): void {
		$invoice = Invoice::factory()
						  ->withAmount(10000)
						  ->create();
		$payment = Payment::factory()
						  ->withAmount(10000)
						  ->withInvoice($invoice)
						  ->create();
		$response = $this->getInvoiceManager()
						 ->approvePayment($payment->getID());
		$this->assertSame($response->getStatus() , PaymentStatus::APPROVED);
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->approvePayment(11);
	}
	
	/**
	 * testing approve payment invalid status
	 * @return void
	 */
	public function test_approvePayment_invalid_status (): void {
		$invoice = Invoice::factory()
						  ->paid()
						  ->create();
		$payment = Payment::factory()
						  ->withAmount(10000)
						  ->withInvoice($invoice)
						  ->create();
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->approvePayment($payment->getID());
	}
	
	/**
	 *Testing approve payment amount mismatch
	 * @return void
	 */
	public function test_approvePayment_amount_mismatch (): void {
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
	 * Testing reject payment
	 * @return void
	 */
	public function test_rejectPayment (): void {
		$invoice = Invoice::factory()
						  ->create();
		$payment = Payment::factory()
						  ->withInvoice($invoice)
						  ->create();
		$response = $this->getInvoiceManager()
						 ->rejectPayment($payment->getID());
		$this->assertSame($response->getStatus() , PaymentStatus::REJECTED);
		$invoice->update([
							 'status' => InvoiceStatus::PAID ,
						 ]);
		$this->expectException(InvalidInvoiceStatusException::class);
		$this->getInvoiceManager()
			 ->rejectPayment($payment->getID());
		$this->expectException(ModelNotFoundException::class);
		$this->getInvoiceManager()
			 ->rejectPayment(11);
	}
}