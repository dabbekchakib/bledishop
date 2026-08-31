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
        'admin_notes',
        'public_token',
        'confirmed_at',
        'completed_at',
        'stock_restored_at',
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
        'stock_restored_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
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

    /**
     * Whether the order was placed without an account.
     */
    public function isGuestOrder(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Full shipping address as a single readable string.
     */
    public function shippingAddressLines(): string
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_postal_code,
            $this->shipping_city,
            $this->shipping_country,
        ], fn (?string $value): bool => filled($value));

        return implode("\n", $parts);
    }

    /**
     * Whether the stock consumed by this order was already restored
     * (cancellation restores stock exactly once, idempotently).
     */
    public function stockWasRestored(): bool
    {
        return $this->stock_restored_at !== null;
    }

    /**
     * Mark the order as having restored its stock.
     */
    public function markStockRestored(): void
    {
        $this->forceFill(['stock_restored_at' => now()])->save();
    }
}
