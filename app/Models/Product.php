<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'brand_id',
        'uom_id',
        'name',
        'sku',
        'slug',
        'part_number',
        'model_no',
        'origin_country',
        'hs_code',
        'purchase_price',
        'sales_price',
        'reorder_level',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
