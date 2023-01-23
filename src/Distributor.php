<?php

namespace dnj\Invoice;

use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Currency\Contracts\IExchangeManager;
use dnj\Invoice\Contracts\IProduct;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Product;
use dnj\Number\Contracts\INumber;
use Illuminate\Support\Facades\DB;

class Distributor
{
    public static string $distributeTransactionType = 'dnj.invoice.product.distribute';

    public function __construct(
        protected AccountLocator $accountLocator,
        protected ITransactionManager $transactionManager,
        protected IExchangeManager $exchangeManager,
        protected IAccountManager $accountManager,
    ) {
    }

    public function distributeInvoice(Invoice $invoice): void
    {
        foreach ($invoice->getProducts() as $product) {
            $this->distributeProduct($product);
        }
    }

    public function distributeProduct(Product $product): void
    {
        if ($this->productAlreadyDistrubted($product)) {
            return;
        }
        $plan = $product->getDistributionPlan();
        foreach ($plan as $accountId => $cut) {
            $plan[$accountId] = $cut->mul($product->getCount());
        }
        $this->distributeBasedOnPlan($product, $plan);
    }

    /**
     * @param array<int,INumber> $plan
     */
    protected function distributeBasedOnPlan(Product $product, array $plan): void
    {
        DB::transaction(function () use ($plan, $product) {
            $fromAccount = $this->accountLocator->getExpenseAccountId($product->getCurrencyId());
            $distribution = [];
            foreach ($plan as $toAccount => $amount) {
                $transaction = $this->transactionManager->transfer($fromAccount, $toAccount, $amount, [
                    'type' => self::$distributeTransactionType,
                    'productId' => $product->getID(),
                ], true);
                $distribution[$toAccount] = $transaction->getID();
            }
            $product->distribution = $distribution;
            $product->save();
        });
    }

    public function productAlreadyDistrubted(IProduct $product): bool
    {
        return !empty($product->getDistribution());
    }
}
