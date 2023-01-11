<?php

namespace dnj\Invoice\Tests\Unit\Concerns;

use dnj\Currency\Contracts\ICurrency;
use dnj\Currency\Contracts\ICurrencyManager;
use dnj\Currency\Contracts\RoundingBehaviour;
use dnj\Currency\Models\Currency;
use dnj\Invoice\Contracts\IInvoiceManager;

trait TestingInvoice {
	public function getInvoiceManager (): IInvoiceManager {
		return $this->app->make(IInvoiceManager::class);
	}
	
	public function getCurrencyManager (): ICurrencyManager {
		return $this->app->make(ICurrencyManager::class);
	}
	
	public function createUSD (): ICurrency {
		return $this->getCurrencyManager()
					->create('USD' , 'US Dollar' , '$' , '' , RoundingBehaviour::CEIL , 2);
	}
	
	public function createEUR (): ICurrency {
		return $this->getCurrencyManager()
					->create('USD' , 'US Dollar' , '$' , '' , RoundingBehaviour::CEIL , 2);
	}
	
	public function products ( Currency $currency ) {
		return [
			[
				'title' => 'product1' ,
				'price' => 125.000 ,
				'discount' => 0.00,
				'count' => 2 ,
				'currencyId' => $currency->id ,
				'distributionPlan' => [
					1 => 3 ,
					2 => 4,
				],
			] ,
			[
				'title' => 'product2' ,
				'price' => 153.000 ,
				'discount' => 120.000 ,
				'count' => 1 ,
				'currencyId' => $currency->id ,
				'distributionPlan' => [
					1 => 2 ,
					2 => 1,
				],
			] ,
		];
	}
}