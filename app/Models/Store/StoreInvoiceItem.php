<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;

/** A line of a Store invoice — its own title and price, not the product's live ones. */
class StoreInvoiceItem extends Model
{
    protected $fillable = [
        'store_invoice_id',
        'store_product_id',
        'title',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'gst_rate',
        'amount',
        'tax',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'gst_rate'   => 'decimal:2',
        'amount'     => 'decimal:2',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(StoreInvoice::class, 'store_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }
}
