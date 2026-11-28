<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTicket extends Model
{
    protected $table = 'service_tickets';

    protected $guarded = ['id'];

    protected $casts = ['resolved_at' => 'datetime'];

    public const CATEGORIES = ['academic', 'id_card', 'fees', 'transport', 'library', 'uniform', 'it', 'welfare', 'general'];

    public const STATUSES = ['open', 'in_progress', 'waiting', 'resolved', 'closed'];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    public function requester()
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_id');
    }
}
