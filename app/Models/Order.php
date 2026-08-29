<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'discount',
        'shipping_amount',
        'tax_amount',
        'total',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country',
        'customer_notes',
        'public_token',
        'confirmed_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'subtotal' => 'integer',
        'discount' => 'integer',
        'shipping_amount' => 'integer',
        'tax_amount' => 'integer',
        'total' => 'integer',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convert a stored integer-cents column into a float for display.
     */
    public function money(string $column): float
    {
        return round(((int) $this->{$column}) / 100, 2);
    }

    public function subtotalAmount(): float
    {
        return $this->money('subtotal');
    }

    public function discountAmount(): float
    {
        return $this->money('discount');
    }

    public function shippingAmount(): float
    {
        return $this->money('shipping_amount');
    }

    public function taxAmount(): float
    {
        return $this->money('tax_amount');
    }

    public function totalAmount(): float
    {
        return $this->money('total');
    }

    /**
     * Full sorted customer name (first + last).
     */
    public function customerFullName(): string
    {
        return trim($this->customer_first_name.' '.$this->customer_last_name);
    }
}
