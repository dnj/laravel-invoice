<?php

namespace dnj\Invoice\Models;

use dnj\Invoice\Casts\DistributionPlan;
use dnj\Invoice\Contracts\IProduct;
use dnj\Invoice\Database\Factories\ProductFactory;
use dnj\Number\Contracts\INumber;
use dnj\Number\Laravel\Casts\Number;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements IProduct
{
    use HasFactory;

    protected $casts = [
        'price' => Number::class,
        'discount' => Number::class,
        'meta' => 'array',
        'distribution_plan' => DistributionPlan::class,
        'distribution' => 'array',
    ];
    protected $guarded = [];
    protected $table = 'invoices_products';

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getInvoiceID(): int
    {
        return $this->invoice_id;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getPrice(): INumber
    {
        return $this->price;
    }

    public function getDiscount(): INumber
    {
        return $this->discount;
    }

    public function getCurrencyId(): int
    {
        return $this->currency_id;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTotalAmount(): INumber
    {
        return $this->getPrice()->sub($this->getDiscount())->mul($this->getCount());
    }

    /**
     * @return array<int,INumber>
     */
    public function getDistributionPlan(): array
    {
        return $this->distribution_plan;
    }

    public function getDistribution(): ?array
    {
        return $this->distribution;
    }

    public function getCreateTime(): int
    {
        return $this->created_at->getTimestamp();
    }

    public function getUpdateTime(): int
    {
        return $this->modified_at?->getTimestamp() ?? $this->getCreateTime();
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected static function newFactory()
    {
        return ProductFactory::new();
    }
}
