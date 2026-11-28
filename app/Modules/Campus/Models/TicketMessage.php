<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $table = 'service_ticket_messages';

    protected $guarded = ['id'];

    protected $casts = ['from_staff' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
