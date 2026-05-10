<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLine extends Model
{
    protected $table = 'transaction_lines';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
