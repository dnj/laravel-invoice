<?php

namespace dnj\Invoice\Models;

use dnj\Account\Models\Transaction;
use dnj\Invoice\Contracts\IPayment;
use dnj\Invoice\Contracts\PaymentStatus;
use dnj\Invoice\Database\Factories\PaymentFactory;
use dnj\Invoice\Models\Invoice;
use dnj\Number\Contracts\INumber;
use dnj\Number\Laravel\Casts\Number;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model implements IPayment {
	
	use HasFactory;
	protected $casts = [
		'amount' => Number::class ,
		'meta' => 'array' ,
	];
	
	public $guarded = [];
	protected $table = "invoice_payments";
	
	public function invoice () {
		return $this->belongsTo(Invoice::class);
	}
	
	public function transaction () {
		return $this->belongsTo(Transaction::class);
	}
	
	public function getID (): int {
		return $this->id;
	}
	
	public function getInvoiceID (): int {
		return $this->invoice_id;
	}
	
	public function getInvoice (): Invoice {
		return $this->invoice;
	}
	
	public function getTransactionId (): ?int {
		return $this->transaction_id;
	}
	
	public function getMethod (): string {
		return $this->method;
	}
	
	public function getAmount (): INumber {
		return $this->amount;
	}
	
	public function getCreateTime (): int {
		return $this->created_at->getTimestamp();
	}
	
	public function getUpdateTime (): int {
		return $this->modified_at?->getTimestamp() ?? $this->getCreateTime();
	}
	
	public function getStatus (): PaymentStatus {
		return $this->status;
	}
	
	public function getMeta (): ?array {
		return $this->meta;
	}
	
	protected static  function newFactory () {
		return PaymentFactory::new();
	}
}
