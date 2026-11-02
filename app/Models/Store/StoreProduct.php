<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;

/**
 * A product SuperLMS sells to schools (Super Admin → Store → Store): title,
 * price, MRP, GST, description, specifications and its images — the first
 * image is the cover.
 */
class StoreProduct extends Model
{
    public const MAX_IMAGES = 10;

    protected $fillable = [
        'title',
        'category',
        'sku',
        'price',
        'mrp',
        'gst_rate',
        'unit',
        'stock',
        'description',
        'specifications',   // [{label, value}]
        'images',           // [url, …]
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'mrp'            => 'decimal:2',
        'gst_rate'       => 'decimal:2',
        'stock'          => 'integer',
        'specifications' => 'array',
        'images'         => 'array',
        'is_active'      => 'boolean',
    ];

    public function coverUrl(): ?string
    {
        return $this->images[0] ?? null;
    }

    /** The price with its GST. */
    public function priceWithTax(): float
    {
        return round((float) $this->price * (1 + (float) $this->gst_rate / 100), 2);
    }
}
