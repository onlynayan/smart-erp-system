<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'transaction_type_id',
        'party_id',
        'transaction_date',
        'invoice_no',
        'status',
        'total_amount',
        'discount_amount',
        'vat_amount',
        'grand_total',
        'paid_amount',
        'due_amount',
        'note',
        'created_by',
    ];

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactionLines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
