<?php

namespace dnj\Invoice\Database\Factories;

use Carbon\Carbon;
use dnj\Currency\Database\Factories\CurrencyFactory;
use dnj\Currency\Models\Currency;
use dnj\Invoice\Enums\InvoiceStatus;
use dnj\Invoice\ModelHelpers;
use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Tests\Factories\UserFactory;
use dnj\Invoice\Tests\Models\User;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    use ModelHelpers;

    protected $model = Invoice::class;

    public function definition()
    {
        // TODO: Implement definition() method.
        $userModel = $this->getUserModel() ?? User::class;

        return [
            'title' => fake()->sentence(3),
            'user_id' => $userModel::factory(),
            'currency_id' => Currency::factory(),
            'amount' => Number::fromInt(0),
            'meta' => null,
            'status' => InvoiceStatus::UNPAID,
        ];
    }

    public function withTitle(string $title)
    {
        return $this->state(fn () => [
            'title' => $title,
        ]);
    }

    public function withUser(User|UserFactory $user)
    {
        return $this->state(fn () => [
            'user_id' => $user,
        ]);
    }

    public function withStatus(InvoiceStatus $status, Carbon $paid_at = null)
    {
        return $this->state(fn () => [
            'status' => $status,
            'paid_at' => $paid_at,
        ]);
    }

    public function withMeta(array $meta)
    {
        return $this->state(fn () => [
            'meta' => $meta,
        ]);
    }

    public function withCurrency(Currency|CurrencyFactory $currency)
    {
        return $this->state(fn () => [
            'currency_id' => $currency,
        ]);
    }

    public function withPaidAt(Carbon $paid_at)
    {
        return $this->state(fn () => [
            'paid_at' => $paid_at,
        ]);
    }

    public function withAmount(string|int|float|INumber $amount)
    {
        return $this->state(fn () => [
            'amount' => Number::fromInput($amount),
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

    public function paid(Carbon $paid_at)
    {
        return $this->withStatus(InvoiceStatus::PAID, $paid_at);
    }

    public function unPaid()
    {
        return $this->withStatus(InvoiceStatus::UNPAID);
    }
}
