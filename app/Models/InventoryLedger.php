<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLedger extends Model
{
    protected $table = 'inventory_ledger';

    const UPDATED_AT = null;

    protected $fillable = [
        'transaction_id',
        'transaction_line_id',
        'product_id',
        'transaction_date',
        'stock_in',
        'stock_out',
        'unit_cost',
        'remarks',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionLine(): BelongsTo
    {
        return $this->belongsTo(TransactionLine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
