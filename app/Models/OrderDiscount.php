<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'discountable_type',
        'discountable_id',
        'kind',
        'code',
        'name',
        'type',
        'value',
        'amount',
    ];

    protected $casts = [
        'discountable_id' => 'integer',
        'value' => 'float',
        'amount' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
