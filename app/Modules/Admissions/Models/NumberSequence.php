<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    use HasFactory;

    protected $table = 'number_sequences';

    protected $fillable = [
        'key_name',
        'year',
        'last_value',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_value' => 'integer',
    ];
}
