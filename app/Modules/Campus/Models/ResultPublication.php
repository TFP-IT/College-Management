<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class ResultPublication extends Model
{
    protected $table = 'result_publications';

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime', 'published_at' => 'datetime'];
}
