<?php

namespace dnj\Invoice;

use dnj\Invoice\Contracts\IInvoiceManager;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/invoice.php', 'invoice.php');
        $this->app->singleton(IInvoiceManager::class, InvoiceManager::class);
        $this->app->singleton(AccountLocator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/invoice.php' => config_path('invoice.php'),
            ], 'config');
        }
    }
}
