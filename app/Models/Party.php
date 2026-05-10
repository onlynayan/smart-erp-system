<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $table = 'parties';

    protected $fillable = [
        'party_type',
        'name',
        'phone',
        'email',
        'address',
    ];
}
