<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class CampusNotification extends Model
{
    protected $table = 'campus_notifications';

    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
