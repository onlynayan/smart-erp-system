<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Uom extends Model
{
    protected $table = 'uoms';

    protected $fillable = [
        'name',
        'short_name',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
