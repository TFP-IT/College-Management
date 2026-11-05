<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payment_gateway_logs';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'provider',
        'direction',
        'payload',
        'signature_valid',
        'http_status',
        'created_at',
        // Alias
        'event_type',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'http_status' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    protected function eventType(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['direction'] ?? null,
            set: fn($v) => ['direction' => in_array(strtoupper($v), ['REQUEST', 'CALLBACK', 'VERIFY'], true) ? strtoupper($v) : 'CALLBACK']
        );
    }

    protected function ipAddress(): Attribute
    {
        return Attribute::make(
            get: fn() => null,
            set: fn($v) => []
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AdmissionPayment::class, 'payment_id');
    }
}
