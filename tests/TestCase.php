<?php

namespace dnj\Invoice\Tests;

use dnj\Account\AccountServiceProvider;
use dnj\Account\Models\Account;
use dnj\Currency\Contracts\ICurrency;
use dnj\Currency\CurrencyServiceProvider;
use dnj\Invoice\AccountLocator;
use dnj\Invoice\Contracts\IInvoiceManager;
use dnj\Invoice\InvoiceManager;
use dnj\Invoice\InvoiceServiceProvider;
use dnj\Invoice\Tests\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        config()->set('invoice.user_model', User::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            CurrencyServiceProvider::class,
            AccountServiceProvider::class,
            InvoiceServiceProvider::class,
        ];
    }

    public function getInvoiceManager(): InvoiceManager
    {
        return $this->app->make(IInvoiceManager::class);
    }

    public function setupExpenseAccount(ICurrency $currency): Account {
        $account = Account::factory()->withCurrency($currency)->create();
        $accountLocator = app(AccountLocator::class);
        $accountLocator->setExpenseAccountId($currency->getID(), $account->getID());
        return $account;
    }
}
