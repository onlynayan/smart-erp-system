<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends Model
{
    protected $table = 'transaction_types';

    protected $fillable = [
        'code',
        'name',
        'direction',
        'affects_stock',
        'affects_payment',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
