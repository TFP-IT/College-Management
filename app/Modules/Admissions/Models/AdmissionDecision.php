<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use App\Modules\Admissions\Enums\CouncilDecision;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDecision extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'admission_decisions';

    protected $fillable = [
        'application_id',
        'decision',
        'decided_by',
        'decided_at',
        'notes',
        'waitlist_rank',
        // Aliases
        'decided_by_user_id',
        'remarks',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'waitlist_rank' => 'integer',
    ];

    public function getDecisionAttribute(): ?CouncilDecision
    {
        $val = $this->attributes['decision'] ?? null;
        return $val ? CouncilDecision::tryFrom($val) : null;
    }

    public function setDecisionAttribute($value): void
    {
        $this->attributes['decision'] = $value instanceof CouncilDecision ? $value->value : (string) $value;
    }

    // Aliases
    protected function decidedByUserId(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['decided_by'] ?? null,
            set: fn($v) => ['decided_by' => $v]
        );
    }

    protected function remarks(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['notes'] ?? null,
            set: fn($v) => ['notes' => $v]
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
