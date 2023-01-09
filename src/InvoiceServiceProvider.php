<?php

namespace dnj\Invoice;

use dnj\Invoice\Contracts\IInvoiceManager;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider {
	public function register () {
		parent::register(); // TODO: Change the autogenerated stub
		$this->mergeConfigFrom(__DIR__ . '/../config/invoice.php' , 'invoice.php');
		$this->app->singleton(IInvoiceManager::class , InvoiceManager::class);
	}
	
	public function boot () {
		$this->_loadMigration();
		$this->_loadConfigs();
	}
	
	private function _loadMigration () {
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
		$this->publishes([
							 __DIR__ . '/../database/migrations' => database_path('migrations') ,
						 ] , 'dnj-invoice-migrations');
	}
	
	private function _loadConfigs () {
		$this->publishes([
							 __DIR__ . '/../config/invoice.php' => config_path('invoice.php') ,
						 ] , 'dnj-invoice-config');
	}
}