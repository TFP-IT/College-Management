<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'application_status_history';

    public $timestamps = false; // migration has created_at only

    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
        'created_at',
        // Aliases
        'changed_by_user_id',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function getFromStatusAttribute(): ?ApplicationStatus
    {
        $val = $this->attributes['from_status'] ?? null;
        return $val ? ApplicationStatus::tryFrom($val) : null;
    }

    public function setFromStatusAttribute($value): void
    {
        $this->attributes['from_status'] = $value instanceof ApplicationStatus ? $value->value : (string) $value;
    }

    public function getToStatusAttribute(): ?ApplicationStatus
    {
        $val = $this->attributes['to_status'] ?? null;
        return $val ? ApplicationStatus::tryFrom($val) : null;
    }

    public function setToStatusAttribute($value): void
    {
        $this->attributes['to_status'] = $value instanceof ApplicationStatus ? $value->value : (string) $value;
    }

    // Aliases
    protected function changedByUserId(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['actor_id'] ?? null,
            set: fn($v) => ['actor_id' => $v]
        );
    }

    protected function remarks(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['reason'] ?? null,
            set: fn($v) => ['reason' => $v]
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
