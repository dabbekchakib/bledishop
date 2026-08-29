<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total',
        'product_snapshot',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'discount_amount' => 'integer',
        'tax_amount' => 'integer',
        'line_total' => 'integer',
        'product_snapshot' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unitPriceAmount(): float
    {
        return round(((int) $this->unit_price) / 100, 2);
    }

    public function lineTotalAmount(): float
    {
        return round(((int) $this->line_total) / 100, 2);
    }
}
