<?php

namespace App\Modules\Admissions\Models;

use App\Modules\Admissions\Enums\SectionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionProgram extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'admission_programs';

    protected $fillable = [
        'code',
        'title',
        'description',
        'section_type',
        'class_id',
        'group_id',
        'academic_year_id',
        'min_eligibility',
        'fee_application',
        'fee_admission',
        'seats',
        'application_opens_at',
        'application_deadline',
        'requires_assessment',
        'is_active',
        // Virtual aliases
        'application_fee',
        'admission_fee',
        'capacity',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'section_type' => SectionType::class,
        'fee_application' => 'decimal:2',
        'fee_admission' => 'decimal:2',
        'seats' => 'integer',
        'application_opens_at' => 'datetime',
        'application_deadline' => 'datetime',
        'requires_assessment' => 'boolean',
        'is_active' => 'boolean',
        'min_eligibility' => 'array',
    ];

    // Aliases
    protected function applicationFee(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => (float) ($attributes['fee_application'] ?? 0),
            set: fn($value) => ['fee_application' => $value]
        );
    }

    protected function admissionFee(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => (float) ($attributes['fee_admission'] ?? 0),
            set: fn($value) => ['fee_admission' => $value]
        );
    }

    protected function capacity(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => (int) ($attributes['seats'] ?? 0),
            set: fn($value) => ['seats' => $value]
        );
    }

    protected function startsAt(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['application_opens_at']) ? $this->asDateTime($attributes['application_opens_at']) : null,
            set: fn($value) => ['application_opens_at' => $value]
        );
    }

    protected function endsAt(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['application_deadline']) ? $this->asDateTime($attributes['application_deadline']) : null,
            set: fn($value) => ['application_deadline' => $value]
        );
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function academicGroup(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'group_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'program_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOpen(Builder $query): Builder
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('application_opens_at')->orWhere('application_opens_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('application_deadline')->orWhere('application_deadline', '>=', $now);
            });
    }

    public function isOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = now();
        $starts = $this->application_opens_at ? $this->application_opens_at <= $now : true;
        $ends = $this->application_deadline ? $this->application_deadline >= $now : true;
        return $starts && $ends;
    }
}
