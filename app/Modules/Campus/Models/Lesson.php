<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'lms_lessons';

    protected $guarded = ['id'];

    protected $casts = ['published_at' => 'datetime'];
}
