<?php

namespace dnj\Invoice;

use dnj\Invoice\Contracts\IInvoiceManager;
use dnj\Invoice\Contracts\IPaymentMethod;
use dnj\Invoice\Enums\InvoiceStatus;
use dnj\Invoice\Enums\PaymentStatus;
use dnj\Invoice\Exceptions\CurrencyMismatchException;
use dnj\Invoice\Exceptions\InvalidInvoiceStatusException;
use dnj\Invoice\Exceptions\InvoiceUserMismatchException;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Payment;
use dnj\Invoice\Models\Product;
use dnj\Number\Contracts\INumber;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class InvoiceManager implements IInvoiceManager
{

    public function create(int $userId, int $currencyId, array $products, array $localizedDetails, ?array $meta): Invoice
    {
        $invoice = new Invoice();
        $invoice->user_id = $userId;
        $invoice->currency_id = $currencyId;
        $invoice->meta = $meta;
        $invoice->title = $localizedDetails['title'];
        $invoice->status = InvoiceStatus::UNPAID;
        $invoice->save();
        $products = $invoice->products()->createMany($products);
        $invoice->recalculateTotalAmount();

        return $invoice;
    }

    public function delete(int $invoiceId): void
    {
        $invoice = $this->getInvoiceById($invoiceId);
        if (InvoiceStatus::UNPAID != $invoice->status) {
            throw new InvalidInvoiceStatusException();
        }
        $invoice->delete();
    }

    public function update(int $invoiceId, array $changes): Invoice
    {
        $invoice = $this->getInvoiceById($invoiceId);
        if (InvoiceStatus::UNPAID != $invoice->status) {
            throw new InvalidInvoiceStatusException();
        }

        return DB::transaction(function () use ($invoice, $changes) {
            $invoice->fill($changes);
            $invoice->save();
            if (isset($changes['products'])) {
                $refresh = false;
                $collection = collect($changes['products']);
                $productIds = array_column($changes['products'], 'id');
                foreach ($invoice->products as $product) {
                    if (!in_array($product->id, $productIds)) {
                        $product->delete();
                        $refresh = true;
                        continue;
                    }
                    $update = $collection->first(fn ($p) => (isset($p['id']) and $p['id'] == $product->id));
                    $product->fill($update);
                    $product->save();
                }
                $invoice->products()->createMany($collection->filter(fn ($p) => !isset($p['id'])));
                if ($refresh) {
                    $invoice->refresh();
                }
            }
            $invoice->recalculateTotalAmount();
            $invoice->checkIfItsJustPaid();

            return $invoice;
        });
    }

    public function addProductToInvoice(int $invoiceId, array $product): Product
    {
        $invoice = $this->getInvoiceById($invoiceId);
        if (InvoiceStatus::UNPAID != $invoice->status) {
            throw new InvalidInvoiceStatusException();
        }

        return DB::transaction(function () use ($invoice, $product) {
            $record = $invoice->products()->create($product);
            $invoice->recalculateTotalAmount();
            $invoice->checkIfItsJustPaid();

            return $record;
        });
    }

    public function updateProduct(int $productId, array $changes): Product
    {
        $product = Product::query()->findOrFail($productId);
        if (InvoiceStatus::UNPAID != $product->invoice->status) {
            throw new InvalidInvoiceStatusException();
        }

        return DB::transaction(function () use ($product, $changes) {
            $product->update($changes);
            $product->invoice->recalculateTotalAmount();
            $product->invoice->checkIfItsJustPaid();

            return $product;
        });
    }

    public function deleteProduct(int $productId): Invoice
    {
        $product = Product::query()->findOrFail($productId);
        if (InvoiceStatus::UNPAID != $product->invoice->status) {
            throw new InvalidInvoiceStatusException();
        }
        $product->delete();
        $product->invoice->recalculateTotalAmount();
        $product->invoice->checkIfItsJustPaid();

        return $product->invoice;
    }

    public function merge(array $invoiceIds, array $localizedDetails): Invoice
    {
        if (count($invoiceIds) < 2) {
            throw new \ArgumentCountError('merge needs more than 2 invoices');
        }

        /**
         * @var Collection<Invoice>
         */
        $invoices = Invoice::query()->whereIn('id', $invoiceIds)->get();
        $missings = [];
        foreach ($invoiceIds as $invoiceId) {
            $found = $invoices->contains(fn ($invoice) => $invoice->id == $invoiceId);
            if (!$found) {
                $missings[] = $invoiceId;
            }
        }
        if ($missings) {
            $e = new ModelNotFoundException();
            $e->setModel(Invoice::class, $missings);

            throw $e;
        }

        foreach ($invoices as $invoice) {
            if (InvoiceStatus::UNPAID != $invoice->getStatus()) {
                throw new InvalidInvoiceStatusException();
            }
            if ($invoice->getUserId() != $invoices[0]->getUserId()) {
                throw new InvoiceUserMismatchException();
            }
            if ($invoice->getCurrencyId() !== $invoices[0]->getCurrencyId()) {
                throw new CurrencyMismatchException();
            }
        }
        $newInvoice = new Invoice();
        $newInvoice->user_id = $invoices[0]->getUserId();
        $newInvoice->currency_id = $invoices[0]->getCurrencyId();
        $meta = ['merged-meta' => []];
        foreach ($invoices as $invoice) {
            $meta['merged-meta'][$invoice->getID()] = $invoice->getMeta();
        }
        $newInvoice->meta = $meta;
        $newInvoice->title = $localizedDetails['title'];
        $totalAmount = Number::fromInt(0);
        foreach ($invoices as $invoice) {
            $totalAmount = $totalAmount->add($invoice->amount);
        }
        $newInvoice->save();
        foreach ($invoices as $invoice) {
            $invoice->products()->update([
                'invoice_id' => $newInvoice->getID(),
            ]);
            $invoice->payments()->update([
                'invoice_id' => $newInvoice->getID(),
            ]);
        }
        $newInvoice->recalculateTotalAmount();
        $newInvoice->checkIfItsJustPaid();

        return $newInvoice;
    }

    public function addPaymentToInvoice(int $invoiceId, string $type, int $currencyId, INumber $amount, ?array $meta): Payment
    {
        if (!is_a($type, IPaymentMethod::class, true)) {
            throw new \TypeError('type must be a implemention of ' . IPaymentMethod::class);
        }

        $invoice = $this->getInvoiceById($invoiceId);
        if (InvoiceStatus::UNPAID != $invoice->getStatus()) {
            throw new InvalidInvoiceStatusException();
        }
        if ($invoice->getAmount()->gt($invoice->getPaidAmount(true)->add($amount))) {
            throw new OverPaymentException($invoice->getAmount(),);
        }

        return $invoice->payments()->create([
            'method' => $type,
            'amount' => $amount,
            'currency_id' => $currencyId,
            'meta' => $meta,
            'status' => PaymentStatus::PENDING,
        ]);
    }

    public function approvePayment(int $paymentId, int $transactionId): Payment
    {
        $payment = Payment::query()->findOrFail($paymentId);
        if (PaymentStatus::PENDING != $payment->getStatus()) {
            throw new InvalidInvoiceStatusException();
        }
        $payment->update([
            'transaction_id' => $transactionId,
            'status' => PaymentStatus::APPROVED,
        ]);
        $payment->invoice->checkIfItsJustPaid();

        return $payment;
    }

    public function rejectPayment(int $paymentId): Payment
    {
        $payment = Payment::query()->findOrFail($paymentId);
        if (PaymentStatus::PENDING != $payment->getStatus()) {
            throw new InvalidInvoiceStatusException();
        }
        $payment->update([
            'status' => PaymentStatus::REJECTED,
        ]);

        return $payment;
    }

    public function getInvoiceById(int $invoiceId): Invoice
    {
        return Invoice::query()->findOrFail($invoiceId);
    }
}
