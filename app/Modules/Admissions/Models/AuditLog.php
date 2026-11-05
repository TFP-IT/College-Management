<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'audit_logs';

    public $timestamps = false; // migration has created_at only

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'old_value',
        'new_value',
        'ip_address',
        'device',
        'created_at',
        // Aliases
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'user_agent',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
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

    // Aliases
    protected function auditableType(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['module'] ?? null,
            set: fn($v) => ['module' => $v]
        );
    }

    protected function auditableId(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['record_id'] ?? null,
            set: fn($v) => ['record_id' => $v]
        );
    }

    protected function before(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => is_array($a['old_value'] ?? null) ? $a['old_value'] : json_decode($a['old_value'] ?? '[]', true),
            set: fn($v) => ['old_value' => is_array($v) ? json_encode($v) : (string) $v]
        );
    }

    protected function after(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => is_array($a['new_value'] ?? null) ? $a['new_value'] : json_decode($a['new_value'] ?? '[]', true),
            set: fn($v) => ['new_value' => is_array($v) ? json_encode($v) : (string) $v]
        );
    }

    protected function userAgent(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['device'] ?? null,
            set: fn($v) => ['device' => $v]
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
