<?php

namespace dnj\Invoice\Database\Factories;

use dnj\Account\Database\Factories\TransactionFactory;
use dnj\Account\Models\Transaction;
use dnj\Invoice\Enums\PaymentStatus;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Payment;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        // TODO: Implement definition() method.
        return [
            'invoice_id' => Invoice::factory(),
            'transaction_id' => null,
            'method' => fake()->sentence(3),
            'amount' => Number::fromInt(0),
            'status' => PaymentStatus::PENDING,
            'meta' => null,
        ];
    }

    public function withMethod(string $method)
    {
        return $this->state(fn () => [
            'method' => $method,
        ]);
    }

    public function withInvoice(Invoice|InvoiceFactory $invoice)
    {
        return $this->state(fn () => [
            'invoice_id' => $invoice,
        ]);
    }

    public function withTransaction(Transaction $transaction)
    {
        return $this->state(fn () => [
            'transaction_id' => $transaction,
        ]);
    }

    public function withAmount(string|int|float|INumber $amount)
    {
        return $this->state(fn () => [
            'amount' => Number::fromInput($amount),
        ]);
    }

    public function withStatus(PaymentStatus $status)
    {
        return $this->state(fn () => [
            'status' => $status,
        ]);
    }

    public function withMeta(array $meta)
    {
        return $this->state(fn () => [
            'meta' => $meta,
        ]);
    }
}
