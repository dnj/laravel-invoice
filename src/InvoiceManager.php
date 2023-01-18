<?php

namespace dnj\Invoice;

use Carbon\Carbon;
use dnj\Invoice\Contracts\IInvoice;
use dnj\Invoice\Contracts\IInvoiceManager;
use dnj\Invoice\Contracts\IPayment;
use dnj\Invoice\Contracts\IProduct;
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
use dnj\Invoice\traits\ProductBuilder;
use dnj\Number\Contracts\INumber;
use Illuminate\Support\Facades\DB;

class InvoiceManager implements IInvoiceManager {
	use ProductBuilder;
	
	public function create ( int $userId , int $currencyId , array $products , array $localizedDetails , ?array $meta ): IInvoice {
		$products = $this->buliderInvoiceProducts($products);
		$totalAmount = $this->recalculateTotalAmount($products);
		$invoice = new Invoice();
		$invoice->user_id = $userId;
		$invoice->currency_id = $currencyId;
		$invoice->meta = $meta;
		$invoice->title = $localizedDetails[ 'title' ];
		$invoice->status = InvoiceStatus::UNPAID;
		$invoice->amount = $totalAmount;
		$invoice->save();
		$invoice->products()
				->createMany($products);
		
		return $invoice;
	}
	
	public function delete ( int $invoiceId ): void {
		$invoice = $this->getInvoiceById($invoiceId);
		if ( InvoiceStatus::PAID == $invoice->status ) {
			throw new InvalidInvoiceStatusException();
		}
		$invoice->delete();
	}
	
	public function update ( int $invoiceId , array $changes ): IInvoice {
		$invoice = $this->getInvoiceById($invoiceId);
		if ( InvoiceStatus::PAID == $invoice->status ) {
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
				$invoice->currency_id = $changes[ 'currencyId' ];
			}
			if ( isset($changes[ 'products' ]) ) {
				$this->updateInvoiceProduct($changes[ 'products' ] , $invoice);
			}
			$invoice->save();
			$invoice->update([
								 'amount' => $invoice->calculatedTotalAmountProducts()
							 ]);
			
			return $invoice;
		});
	}
	
	public function addProductToInvoice ( int $invoiceId , array $product ): IProduct {
		$invoice = $this->getInvoiceById($invoiceId);
		
		return DB::transaction(function () use ( $invoice , $product ) {
			$record = $invoice->products()
							  ->create([
										   'title' => $product[ 'title' ] ,
										   'price' => $product[ 'price' ] ,
										   'discount' => $product[ 'discount' ] ,
										   'currency_id' => $product[ 'currencyId' ] ,
										   'count' => $product[ 'count' ] ,
										   'description' => $product[ 'description' ] ,
										   'distribution_plan' => json_encode($product[ 'distributionPlan' ] , JSON_THROW_ON_ERROR) ,
										   'distribution' => json_encode($product[ 'distribution' ] , JSON_THROW_ON_ERROR) ,
										   'meta' => json_encode($product[ 'meta' ] , JSON_THROW_ON_ERROR) ,
									   ]);
			$invoice->update([
								 'amount' => $record->getTotalAmount() ,
							 ]);
			
			return $record;
		});
	}
	
	public function updateProduct ( int $productId , array $changes ): IProduct {
		$product = Product::query()
						  ->findOrFail($productId);
		
		return DB::transaction(function () use ( $product , $changes ) {
			if ( isset($changes[ 'title' ]) ) {
				$product->title = $changes[ 'title' ];
			}
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
				$product->distribution_plan = $changes[ 'distributionPlan' ];
			}
			if ( isset($changes[ 'distribution' ]) ) {
				$product->distribution = $changes[ 'distribution' ];
			}
			if ( isset($changes[ 'description' ]) ) {
				$product->description = $changes[ 'description' ];
			}
			if ( isset($changes[ 'meta' ]) ) {
				$product->meta = $changes[ 'meta' ];
			}
			if ( isset($changes[ 'currencyId' ]) ) {
				$product->currency_id = $changes[ 'currencyId' ];
			}
			$product->save();
			$product->update([
								 'total_amount' => $product->getTotalAmount() ,
							 ]);
			
			return $product;
		});
	}
	
	public function deleteProduct ( int $productId ): IInvoice {
		$product = Product::query()
						  ->findOrFail($productId);
		$invoice = $this->getInvoiceById($product->invoice_id);
		$product->delete();
		
		return $invoice;
	}
	
	public function merge ( array $invoiceIds , array $localizedDetails ): IInvoice {
		$first_invoice = $this->getInvoiceById($invoiceIds[ 0 ]);
		$second_invoice = $this->getInvoiceById($invoiceIds[ 1 ]);
		
		return DB::transaction(function () use ( $first_invoice , $second_invoice , $localizedDetails ) {
			if ( InvoiceStatus::PAID == $first_invoice->getStatus() ) {
				throw new InvalidInvoiceStatusException();
			}
			if ( InvoiceStatus::PAID == $second_invoice->getStatus() ) {
				throw new InvalidInvoiceStatusException();
			}
			if ( $first_invoice->getUserId() != $second_invoice->getUserId() ) {
				throw new InvoiceUserMismatchException();
			}
			if ( $first_invoice->getCurrencyId() !== $second_invoice->getCurrencyId() ) {
				throw new CurrencyMismatchException();
			}
			
			return $this->createInvoice($first_invoice , $second_invoice , $localizedDetails);
		});
	}
	
	protected function createInvoice ( Invoice $first_invoice , Invoice $second_invoice , array $localizedDetails ): IInvoice {
		$invoice = new Invoice();
		$invoice->user_id = $first_invoice->getUserId();
		$invoice->currency_id = $first_invoice->getCurrencyId();
		if ( null != $first_invoice->getMeta() || null != $second_invoice->getMeta() ) {
			$invoice->meta = array_merge($first_invoice->getMeta() ?? [] , $second_invoice->getMeta() ?? []);
		}
		$invoice->amount = $first_invoice->amount->getValue() + $second_invoice->amount->getValue();
		if ( isset($localizedDetails[ 'title' ]) ) {
			$invoice->title = $localizedDetails[ 'title' ];
		}
		$invoice->save();
		$first_invoice->products()
					  ->update([
								   'invoice_id' => $invoice->getID() ,
							   ]);
		$second_invoice->products()
					   ->update([
									'invoice_id' => $invoice->getID() ,
								]);
		
		return $invoice;
	}
	
	public function addPaymentToInvoice ( int $invoiceId , string $type , INumber $amount , PaymentStatus $status , ?array $meta , int $currencyId ): IPayment {
		$invoice = $this->getInvoiceById($invoiceId);
		if ( InvoiceStatus::PAID == $invoice->getStatus() ) {
			throw new InvalidInvoiceStatusException();
		}
		$totalAmount = $invoice->getAmount()
							   ->sub($invoice->getTotalPaidAmount());
		if ( $totalAmount->getValue() == 0 ) {
			throw new FinishedInvoicePaymentsException();
		}
		if ( $amount > $totalAmount ) {
			throw new AmountInvoiceMismatchException();
		}

		
		return $invoice->payments()
					   ->create([
									'method' => $type ,
									'amount' => $amount ,
									'currency_id' => $currencyId ,
									'meta' => $meta ,
									'status' => $status ,
								]);
	}
	
	public function approvePayment ( int $paymentId ): IPayment {
		$payment = Payment::query()
						  ->findOrFail($paymentId);
		if ( PaymentStatus::APPROVED == $payment->getStatus() ) {
			throw new InvalidInvoiceStatusException();
		}
		$payment->update([
							 'status' => PaymentStatus::APPROVED ,
						 ]);
		$this->paidInvoice($payment->invoice);
		
		return $payment;
	}
	
	private function paidInvoice ( Invoice $invoice ) {
		$totalCountInvoicePayments = $invoice->payments()
											 ->count();
		$totalCountApprovedPayments = $invoice->payments()
											  ->where('status' , PaymentStatus::APPROVED)
											  ->count();
		if ( $totalCountInvoicePayments == $totalCountApprovedPayments ) {
			$invoice->update([
								 'paid_at' => Carbon::now() ,
								 'status' => InvoiceStatus::PAID ,
							 ]);
		}
	}
	
	public function rejectPayment ( int $paymentId ): IPayment {
		$payment = Payment::query()
						  ->findOrFail($paymentId);
		if ( PaymentStatus::APPROVED == $payment->getStatus() ) {
			throw new InvalidInvoiceStatusException();
		}
		$payment->update([
							 'status' => PaymentStatus::REJECTED ,
						 ]);
		
		return $payment;
	}
	
	public function getInvoiceById ( int $invoiceId ): Invoice {
		return Invoice::query()
					  ->findOrFail($invoiceId);
	}
}
