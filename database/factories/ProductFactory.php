<?php

namespace dnj\Invoice\Database\Factories;

use dnj\Currency\Database\Factories\CurrencyFactory;
use dnj\Currency\Models\Currency;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Product;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'title' => fake()->sentence(3),
            'invoice_id' => Invoice::factory(),
            'currency_id' => Currency::factory(),
            'price' => Number::fromInt(0),
            'discount' => Number::fromInt(0),
            'total_amount' => Number::fromInt(0),
            'count' => fake()->numberBetween($min = 1, $max = 10),
            'meta' => null,
            'distribution_plan' => null,
            'distribution' => null,
            'description' => null,
        ];
    }

    public function withCurrency(Currency|CurrencyFactory $currency)
    {
        return $this->state(fn () => [
            'currency_id' => $currency,
        ]);
    }

    public function withTitle(string $title)
    {
        return $this->state(fn () => [
            'title' => $title,
        ]);
    }

    public function withDistributionPlan(array $DistributionPlan)
    {
        return $this->state(fn () => [
            'distribution_plan' => $DistributionPlan,
        ]);
    }

    public function withDistribution(array $distribution)
    {
        return $this->state(fn () => [
            'distribution' => $distribution,
        ]);
    }

    public function withMeta(array $meta)
    {
        return $this->state(fn () => [
            'meta' => $meta,
        ]);
    }

    public function withDescription(string $description)
    {
        return $this->state(fn () => [
            'description' => $description,
        ]);
    }

    public function withCount(int $count)
    {
        return $this->state(fn () => [
            'count' => $count,
        ]);
    }

    public function withInvoice(Invoice|InvoiceFactory $invoice)
    {
        return $this->state(fn () => [
            'invoice_id' => $invoice,
        ]);
    }

    public function withPrice(string|int|float|INumber $price)
    {
        return $this->state(fn () => [
            'price' => Number::fromInput($price),
        ]);
    }

    public function withDiscount(string|int|float|INumber $discount)
    {
        return $this->state(fn () => [
            'discount' => Number::fromInput($discount),
        ]);
    }

    public function withTotalAmount(string|int|float|INumber $totalAmount)
    {
        return $this->state(fn () => [
            'total_amount' => $totalAmount,
        ]);
    }

    public function withUSD()
    {
        return $this->withCurrency(Currency::factory()
                                           ->create()
                                           ->asUSD());
    }

    public function withEUR()
    {
        return $this->withCurrency(Currency::factory()
                                           ->create()
                                           ->asEUR());
    }
}
