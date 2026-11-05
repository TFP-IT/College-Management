<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use App\Modules\Admissions\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionAssessment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'admission_assessments';

    protected $fillable = [
        'application_id',
        'assessment_type',
        'scheduled_at',
        'venue',
        'score',
        'max_score',
        'evaluator_id',
        'remarks',
        // Aliases
        'total_marks',
        'obtained_marks',
        'comments',
        'evaluated_by_user_id',
        'evaluated_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function getAssessmentTypeAttribute(): ?AssessmentType
    {
        $val = $this->attributes['assessment_type'] ?? null;
        return $val ? AssessmentType::tryFrom($val) : null;
    }

    public function setAssessmentTypeAttribute($value): void
    {
        $this->attributes['assessment_type'] = $value instanceof AssessmentType ? $value->value : (string) $value;
    }

    // Aliases
    protected function totalMarks(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => (float) ($a['max_score'] ?? 100),
            set: fn($v) => ['max_score' => $v]
        );
    }

    protected function obtainedMarks(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => isset($a['score']) ? (float) $a['score'] : null,
            set: fn($v) => ['score' => $v]
        );
    }

    protected function comments(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['remarks'] ?? null,
            set: fn($v) => ['remarks' => $v]
        );
    }

    protected function evaluatedByUserId(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['evaluator_id'] ?? null,
            set: fn($v) => ['evaluator_id' => $v]
        );
    }

    protected function evaluatedAt(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => isset($a['updated_at']) ? $this->asDateTime($a['updated_at']) : null,
            set: fn($v) => []
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
