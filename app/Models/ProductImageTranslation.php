<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImageTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_image_id',
        'locale',
        'alt',
    ];

    public function productImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class);
    }
}
