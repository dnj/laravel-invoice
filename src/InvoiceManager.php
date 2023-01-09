<?php

namespace dnj\Invoice;

use dnj\INovice\Models\Payment;
use dnj\INovice\Models\Product;
use dnj\Invoice\Contracts\IInvoice;
use dnj\Invoice\Contracts\IInvoiceManager;
use dnj\Invoice\Contracts\InvoiceStatus;
use dnj\Invoice\Contracts\IPayment;
use dnj\Invoice\Contracts\IProduct;
use dnj\Invoice\Contracts\PaymentStatus;
use dnj\Invoice\Exceptions\CurrencyMismatchException;
use dnj\Invoice\Exceptions\InvalidInvoiceStatusException;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\traits\ProductBuilder;
use dnj\Number\Contracts\INumber;
use Illuminate\Support\Facades\DB;

class InvoiceManager implements IInvoiceManager {
	use ProductBuilder;
	
	public function create ( int $userId , int $currencyId , array $products , array $localizedDetails , ?array $meta ): IInvoice {
		// TODO: Implement create() method.
		$invoice = new Invoice();
		$invoice->user_id = $userId;
		$invoice->currencyId = $currencyId;
		$invoice->meta = $meta;
		if ( isset($localizedDetails[ 'title' ]) ) {
			$invoice->title = $localizedDetails[ 'title' ];
		}
		$invoice->save();
		$invoice->products()
				->createMany($this->buliderInvoiceProducts($products));
		$invoice->amount = $this->calculationTotalAmountProduct($products);
		
		return $invoice;
		//$invoice->pro
	}
	
	public function delete ( int $invoiceId ): void {
		// TODO: Implement delete() method.
		$invoice = $this->getById($invoiceId);
		if ( $invoice->status == InvoiceStatus::PAID ) {
			throw new InvalidInvoiceStatusException();
		}
		if ( $invoice->products->isNotEmpty() ) {
			$invoice->products()
					->delete();
		}
		if ( $invoice->payments->isNotEmpty() ) {
			$invoice->payments()
					->delete();
		}
		$invoice->delete();
	}
	
	public function update ( int $invoiceId , array $changes ): IInvoice {
		// TODO: Implement update() method.
		$invoice = $this->getById($invoiceId);
		if ( $invoice->status == InvoiceStatus::PAID ) {
			throw new InvalidInvoiceStatusException();
		}
		
		return DB::transaction(function () use ( $invoice , $changes ) {
			if ( isset($changes[ 'title' ]) ) {
				$invoice->title = $changes[ 'title' ];
			}
			if ( isset($changes[ 'user_id' ]) ) {
				$invoice->user_id = $changes[ 'user_id' ];
			}
			if ( isset($changes[ 'meta' ]) ) {
				$invoice->meta = $changes[ 'meta' ];
			}
			if ( isset($changes[ 'currencyId' ]) ) {
				$invoice->currencyId = $changes[ 'currencyId' ];
			}
			if ( isset($changes[ 'products' ]) ) {
				$invoice->products()
						->delete();
				$invoice->products()
						->createMany($this->buliderInvoiceProducts($changes[ 'products' ]));
				$invoice->amount = $this->calculationTotalAmountProduct($changes[ 'products' ]);
			}
			$invoice->save();
			
			return $invoice;
		});
	}
	
	public function addProductToInvoice ( int $invoiceId , array $products ): IProduct {
		// TODO: Implement addProductToInvoice() method.
		$invoice = $this->getById($invoiceId);
		
		return DB::transaction(function () use ( $invoice , $products ) {
			$invoice->products()
					->createMany($this->buliderInvoiceProducts($products));
			$invoice->amount = $this->calculationTotalAmountProduct($products);
			$invoice->save();
			
			return $invoice->products;
		});
	}
	
	public function updateProduct ( int $productId , array $changes ): IProduct {
		// TODO: Implement updateProduct() method.
		$product = Product::query()
						  ->where('id' , $productId)
						  ->first();
		
		return DB::transaction(function () use ( $product , $changes ) {
			
			if ( isset($changes[ 'price' ]) ) {
				$product->price = $changes[ 'price' ];
			}
			if ( isset($changes[ 'discount' ]) ) {
				$product->discount = $changes[ 'discount' ];
			}
			if ( isset($changes[ 'count' ]) ) {
				$product->count = $changes[ 'count' ];
			}
			if ( isset($changes[ 'distributionPlan' ]) ) {
				$product->distributionPlan = $changes[ 'distributionPlan' ];
			}
			if ( isset($changes[ 'distribution' ]) ) {
				$product->distribution = $changes[ 'distribution' ];
			}
			if ( isset($changes[ 'description' ]) ) {
				$product->description = $changes[ 'description' ];
			}
			if ( isset($changes[ 'discount' ] , $changes[ 'count' ]) ) {
				$product->total_amount = $changes[ 'count' ] * $changes[ 'discount' ];
			}
			else {
				$product->total_amount = $changes[ 'count' ] * $changes[ 'price' ];
			}
			$product->save();
			
			return $product;
		});
	}
	
	public function deleteProduct ( int $productId ): IInvoice {
		// TODO: Implement deleteProduct() method.
		$product = Product::query()
						  ->where('id' , $productId)
						  ->firstOrFail();
		$invoice = $this->getById($product->invoice_id);
		$product->delete();
		
		return $invoice;
	}
	
	public function merge ( array $invoiceIds , array $localizedDetails ): IInvoice {
		// TODO: Implement merge() method.
		$first_invoice = $this->getInvoiceById($invoiceIds[ 0 ]);
		$second_invoice = $this->getInvoiceById($invoiceIds[ 1 ]);
		
		return DB::transaction(function () use ( $first_invoice , $second_invoice , $localizedDetails ) {
			
			
			if ( $first_invoice->status == InvoiceStatus::PAID ) {
				throw  new InvalidInvoiceStatusException();
			}
			if ( $second_invoice->status == InvoiceStatus::PAID ) {
				throw new InvalidInvoiceStatusException();
			}
			if ( $first_invoice->getCurrencyId() !== $second_invoice->getCurrencyId() ) {
				throw new CurrencyMismatchException();
			}
			$this->createInvoice($first_invoice , $second_invoice , $localizedDetails);
		});
	}
	
	protected function createInvoice ( Invoice $first_invoice , Invoice $second_invoice , array $localizedDetails ): IInvoice {
		$invoice = new Invoice();
		$invoice->user_id = $first_invoice->user_id;
		$invoice->currencyId = $first_invoice->currency_id;
		$invoice->meta = array_map($first_invoice->meta , $second_invoice->meta);
		$invoice->amount = $first_invoice->amount + $second_invoice->amount;
		if ( isset($localizedDetails[ 'title' ]) ) {
			$invoice->title = $localizedDetails[ 'title' ];
		}
		$invoice->save();
		$first_invoice->products()
					  ->update([
								   'invoice_id' => $invoice->id ,
							   ]);
		$second_invoice->products()
					   ->update([
									'invoice_id' => $invoice->id ,
								]);
		
		return $invoice;
	}
	
	public function addPaymentToInvoice ( int $invoiceId , string $type , INumber $amount , PaymentStatus $status , ?array $meta ): IPayment {
		// TODO: Implement addPaymentToInvoice() method.
		$payment = new Payment();
		$payment->invoice_id = $invoiceId;
		$payment->method = $type;
		$payment->amount = $amount;
		$payment->meta = $meta;
		$payment->status = $status;
		$payment->save();
		
		return $payment;
	}
	
	public function approvePayment ( int $paymentId ): IPayment {
		// TODO: Implement approvePayment() method.
		$payment = Payment::query()
						  ->findOrFail($paymentId);
		$payment->update([
							 'status' => PaymentStatus::APPROVED ,
						 ]);
		
		return $payment;
	}
	
	public function rejectPayment ( int $paymentId ): IPayment {
		// TODO: Implement rejectPayment() method.
		$payment = Payment::query()
						  ->findOrFail($paymentId);
		$payment->update([
							 'status' => PaymentStatus::REJECTED ,
						 ]);
		
		return $payment;
	}
	
	public function getInvoiceById ( int $invoiceId ): IInvoice {
		// TODO: Implement getById() method.
		return Invoice::query()
					  ->where('id' , $invoiceId)
					  ->with('products')
					  ->firstOrFail();
	}
}