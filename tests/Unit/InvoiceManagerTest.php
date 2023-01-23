<?php

namespace dnj\Invoice\Tests\Unit;

use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Account\TransactionManager;
use dnj\Currency\Models\Currency;
use dnj\Invoice\Contracts\IPaymentMethod;
use dnj\Invoice\Enums\InvoiceStatus;
use dnj\Invoice\Enums\PaymentStatus;
use dnj\Invoice\Exceptions\InvalidInvoiceStatusException;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Payment;
use dnj\Invoice\Models\Product;
use dnj\Invoice\Tests\Models\User;
use dnj\Invoice\Tests\TestCase;
use dnj\Number\Number;

class InvoiceManagerTest extends TestCase
{
    public function testCreateInvoice(): void
    {
        $now = time();
        $user = User::factory()->create();
        $USD = Currency::factory()->asUSD()->create();
        $accounts = Account::factory(2)->create();
        $products = [
            [
                'title' => 'product1',
                'price' => 125.000,
                'discount' => 0.00,
                'count' => 2,
                'currency_id' => $USD->getID(),
                'distribution_plan' => [
                    $accounts[0]->getID() => Number::fromInput(100),
                    $accounts[1]->getID() => Number::fromInput(25),
                ],
            ],
            [
                'title' => 'product2',
                'price' => 153.000,
                'discount' => 120.000,
                'count' => 1,
                'currency_id' => $USD->getID(),
                'distribution_plan' => [
                    $accounts[0]->getID() => Number::fromInput(3),
                    $accounts[1]->getID() => Number::fromInput(30),
                ],
            ],
        ];
        $invoice = $this->getInvoiceManager()->create($user->id, $USD->getID(), $products, ['title' => 'invoice one'], []);
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame($USD->getID(), $invoice->currency_id);
        $this->assertSame($now, $invoice->getCreateTime());
        $this->assertCount(2, $invoice->getProducts());
        $this->assertSame((125 - 0) * 2 + (153 - 120) * 1, $invoice->getAmount()->getValue());
    }

    public function testDeleteInvoice(): void
    {
        $invoice = Invoice::factory()
            ->unpaid()
            ->has(Product::factory())
            ->create();
        $this->getInvoiceManager()->delete($invoice->getID());
        $this->assertModelMissing($invoice);
    }

    public function testDeletePaidInvoice(): void
    {
        $invoice = Invoice::factory()->paid()->create();
        $this->expectException(InvalidInvoiceStatusException::class);
        $this->getInvoiceManager()->delete($invoice->getID());
    }

    public function testUpdateInvoice()
    {
        $user = User::factory()->create();
        $EUR = Currency::factory()->asEUR()->create();
        
        $invoice = Invoice::factory()
            ->unpaid()
            ->has(Product::factory(2))
            ->create();
        [$product1, $product2] = $invoice->products;
        $accounts = Account::factory(2)->withCurrency($EUR)->create();

        $data = [
            'title' => 'update invoice one',
            'user_id' => $user->id,
            'meta' => [
                'key1' => 'value',
            ],
            'currency_id' => $EUR->getID(),
            'products' => [
                [
                    'id' => $product1->id,
                    'title' => 'this is a title '.$product1->id,
                    'price' => 125.00,
                    'discount' => 100.00,
                    'count' => 2,
                    'currency_id' => $EUR->getID(),
                    'meta' => ['key_meta' => 'value_meta'],
                    'distribution_plan' => [
                        $accounts[0]->getID() => Number::fromInput(20),
                        $accounts[1]->getID() => Number::fromInput(5),
                    ],
                    'description' => 'this is a test',
                ],
                [
                    'title' => 'add new product',
                    'price' => 300.00,
                    'discount' => 150.00,
                    'count' => 2,
                    'distribution_plan' => [
                        $accounts[0]->getID() => Number::fromInput(100),
                        $accounts[1]->getID() => Number::fromInput(50),
                    ],
                    'currency_id' => $EUR->getID(),
                ],
            ],
        ];
        $invoice = $this->getInvoiceManager()->update($invoice->getID(), $data);
        $this->assertSame((125 - 100) * 2 + (300 - 150) * 2, $invoice->getAmount()->getValue());
        $this->assertSame(0, $invoice->getPaidAmount()->getValue());
        $this->assertSame(InvoiceStatus::UNPAID, $invoice->getStatus());
        $this->assertSame($EUR->getID(), $invoice->getCurrencyId());
        $this->assertSame($data['meta'], $invoice->getMeta());
        $this->assertSame($data['title'], $invoice->getTitle());
        $this->assertSame($data['user_id'], $invoice->getUserId());

        $this->assertCount(2, $invoice->products);
        $this->assertModelExists($product1);
        $this->assertModelMissing($product2);
        $newProduct = $invoice->products()->whereNot('id', $product1->id)->first();
        $this->assertSame($data['products'][1]['title'], $newProduct->title);
    }

    public function testAddProductToInvoice()
    {
        $USD = Currency::factory()->asUSD()->create();
        $invoice = Invoice::factory()->withCurrency($USD)->create();
        $data = [
            'title' => 'product3',
            'price' => 300,
            'discount' => 50.00,
            'currency_id' => $USD->getID(),
            'count' => 3,
            'description' => 'this is a test',
            'distribution_plan' => [
                'key' => 'value',
            ],
            'meta' => [
                'key2' => 'value2',
            ],
        ];
        $product = $this->getInvoiceManager()->addProductToInvoice($invoice->getID(), $data);
        $this->assertSame($data['title'], $product->getTitle());
        $this->assertSame($data['count'], $product->getCount());
    }

    public function testUpdateProduct()
    {
        $USD = Currency::factory()->asUSD()->create();
        $invoice = Invoice::factory()
            ->withCurrency($USD)
            ->has(Product::factory(2)->withCurrency($USD))
            ->create();
        $product = $invoice->products[0];
        $changes = [
            'title' => 'new title',
            'price' => 300,
            'discount' => 50.00,
            'count' => 2,
        ];
        $product = $this->getInvoiceManager()->updateProduct($product->id, $changes);
        $this->assertSame($changes['title'], $product->getTitle());
        $this->assertSame($changes['count'], $product->getCount());
        $this->assertSame(500, $product->getTotalAmount()->getValue());
    }

    public function testDeleteProduct()
    {
        $USD = Currency::factory()->asUSD()->create();
        $invoice = Invoice::factory()
            ->withCurrency($USD)
            ->has(Product::factory(2)->withCurrency($USD))
            ->create();
        $invoice->recalculateTotalAmount();
        $invoice->recalculateTotalAmount(); // Double call on purpose to test if amount already was correct no extra query execute.

        $product = $invoice->products[0];
        $totalAmount = $invoice->getAmount();
        $invoice = $this->getInvoiceManager()->deleteProduct($product->id);
        $this->assertModelMissing($product);
        $this->assertTrue($totalAmount->sub($product->getTotalAmount())->eq($invoice->getAmount()));
        $this->assertCount(1, $invoice->getProducts());
    }

    public function testMerge()
    {
        $user = User::factory()->create();
        $USD = Currency::factory()->asUSD()->create();
        $invoices = Invoice::factory(2)
            ->withCurrency($USD)
            ->withUser($user)
            ->has(Product::factory()->withCurrency($USD))
            ->has(Payment::factory()->withCurrency($USD))
            ->create()
            ->each(fn($i) => $i->recalculateTotalAmount());
        $newInvoice = $this->getInvoiceManager()->merge($invoices->pluck("id")->all(), array(
            'title' => 'merged invoice',
        ));
        $this->assertSame('merged invoice', $newInvoice->getTitle());
        $this->assertCount(2, $newInvoice->getProducts());
        $this->assertCount(2, $newInvoice->getPayments());
        $this->assertSame($invoices->map(fn(Invoice $i) => $i->getAmount()->getValue())->sum(), $newInvoice->getAmount()->getValue());
        $this->assertSame($invoices->map(fn(Invoice $i) => $i->getPaidAmount(true)->getValue())->sum(), $newInvoice->getPaidAmount(true)->getValue());
    }

    public function testAddPaymentToInvoiceAndReject()
    {
        $USD = Currency::factory()->asUSD()->create();

        /**
         * @var Invoice
         */
        $invoice = Invoice::factory()
            ->withCurrency($USD)
            ->has(Product::factory()->withCurrency($USD))
            ->create();
        $invoice->recalculateTotalAmount();
        
        $method = new class() implements IPaymentMethod {};
        $now = time();
        $payment = $this->getInvoiceManager()->addPaymentToInvoice(
            $invoice->id, 
            get_class($method),
            $USD->getID(),
            $invoice->getAmount(),
            ['key' => 'value']
        );
        $this->assertSame($invoice->getID(), $payment->getInvoiceID());
        $this->assertSame($invoice->getID(), $payment->getInvoice()->getID());
        $this->assertSame(get_class($method), $payment->getMethod());
        $this->assertSame(['key' => 'value'], $payment->getMeta());
        $this->assertSame($now, $payment->getCreateTime());
        $this->assertSame($now, $payment->getUpdateTime());
        $this->assertSame(0, $payment->invoice->getPaidAmount(false)->getValue());
        $this->assertTrue($payment->invoice->getPaidAmount(true)->eq($payment->getAmount()));
        $this->assertSame(InvoiceStatus::UNPAID, $payment->invoice->getStatus());
        $this->assertSame(PaymentStatus::PENDING, $payment->getStatus());

        $payment = $this->getInvoiceManager()->rejectPayment($payment->getID());
        $this->assertSame(0, $payment->invoice->getPaidAmount(false)->getValue());
        $this->assertSame(0, $payment->invoice->getPaidAmount(true)->getValue());
        $this->assertSame(InvoiceStatus::UNPAID, $payment->invoice->getStatus());
        $this->assertSame(PaymentStatus::REJECTED, $payment->getStatus());
    }

    public function testApprovePayment()
    {
        $USD = Currency::factory()->asUSD()->create();
        $userAccount = Account::factory()->withCurrency($USD)->create();
        $systemAccount = $this->setupExpenseAccount($USD);
    
        $profitAccount = Account::factory()->withCurrency($USD)->create();
        $savingAccount = Account::factory()->withCurrency($USD)->create();
        $taxAccount = Account::factory()->withCurrency($USD)->create();

        /**
         * @var ITransactionManager
         */
        $transactionManager = app(ITransactionManager::class);
        $transaction = $transactionManager->transfer($userAccount->getID(), $systemAccount->getID(), Number::fromInput(200), null, true);

        $distributionPlan = array(
            $profitAccount->getID() => Number::fromInput(30),
            $taxAccount->getID() => Number::fromInput(9),
            $savingAccount->getID() => Number::fromInput(61),
        );
        /**
         * @var Invoice
         */
        $invoice = Invoice::factory()
            ->withCurrency($USD)
            ->has(Product::factory()
                ->withCurrency($USD)
                ->withDistributionPlan($distributionPlan)
                ->withPrice(150)
                ->withDiscount(50)
                ->withCount(2))
            ->create();
        $invoice->recalculateTotalAmount();
        
        $method = new class() implements IPaymentMethod {};

        $payment = $this->getInvoiceManager()->addPaymentToInvoice(
            $invoice->id, 
            get_class($method),
            $USD->getID(),
            $invoice->getAmount(),
            ['key' => 'value']
        );
        $payment = $this->getInvoiceManager()->approvePayment($payment->getID(), $transaction->getID());
        $invoice->refresh();
        $this->assertSame(0, $invoice->getUnpaidAmount(true)->getValue());
        $this->assertTrue($invoice->getPaidAmount(false)->eq($payment->getAmount()));
        $this->assertSame(InvoiceStatus::PAID, $invoice->getStatus());
        $this->assertSame(PaymentStatus::APPROVED, $payment->getStatus());
        $this->assertSame($transaction->getID(), $payment->getTransactionId());
        $this->assertDatabaseHas(Transaction::class, array(
            'from_id' => $systemAccount->getID(),
            'to_id' => $profitAccount->getID(),
            'amount' => 30 * 2,
        ));
        $this->assertDatabaseHas(Transaction::class, array(
            'from_id' => $systemAccount->getID(),
            'to_id' => $savingAccount->getID(),
            'amount' => 61 * 2,
        ));
        $this->assertDatabaseHas(Transaction::class, array(
            'from_id' => $systemAccount->getID(),
            'to_id' => $taxAccount->getID(),
            'amount' => 9 * 2,
        ));
    }

}
