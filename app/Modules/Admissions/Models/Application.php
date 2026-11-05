<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'applications';

    protected $fillable = [
        'application_no',
        'program_id',
        'applicant_user_id',
        'applicant_name',
        'email',
        'mobile',
        'dob',
        'gender',
        'blood_group',
        'current_address',
        'permanent_address',
        'previous_school',
        'previous_gpa',
        'academic_history',
        'status',
        'submitted_at',
        'qr_token_hash',
        'qr_token',
        'qr_expires_at',
        'qr_used_at',
        'withdrawn_at',
        'enrolled_student_id',
        'council_notes',
    ];

    protected $casts = [
        'dob' => 'date',
        'academic_history' => 'array',
        'previous_gpa' => 'decimal:2',
        'submitted_at' => 'datetime',
        'qr_expires_at' => 'datetime',
        'qr_used_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    /** Typed status. */
    public function getApplicationStatusAttribute(): ApplicationStatus
    {
        return ApplicationStatus::tryFrom((string) ($this->attributes['status'] ?? '')) ?? ApplicationStatus::DRAFT;
    }

    public function setApplicationStatusAttribute($value): void
    {
        $this->attributes['status'] = $value instanceof ApplicationStatus ? $value->value : (string) $value;
    }

    // Readable aliases used by views, resources and older code.
    protected function applicationNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => $attr['application_no'] ?? null,
            set: fn ($val) => ['application_no' => $val]
        );
    }

    protected function candidateName(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => $attr['applicant_name'] ?? null,
            set: fn ($val) => ['applicant_name' => $val]
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => $attr['mobile'] ?? null,
            set: fn ($val) => ['mobile' => $val]
        );
    }

    protected function candidateDob(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => isset($attr['dob']) ? $this->asDate($attr['dob']) : null,
            set: fn ($val) => ['dob' => $val]
        );
    }

    protected function createdByUserId(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => $attr['applicant_user_id'] ?? null,
            set: fn ($val) => ['applicant_user_id' => $val]
        );
    }

    protected function qrTokenExpiresAt(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => isset($attr['qr_expires_at']) ? $this->asDateTime($attr['qr_expires_at']) : null,
            set: fn ($val) => ['qr_expires_at' => $val]
        );
    }

    protected function qrScannedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($val, $attr) => isset($attr['qr_used_at']) ? $this->asDateTime($attr['qr_used_at']) : null,
            set: fn ($val) => ['qr_used_at' => $val]
        );
    }

    protected function admissionStudentId(): Attribute
    {
        return Attribute::make(
            get: function ($val, $attr) {
                if (!empty($attr['enrolled_student_id'])) {
                    $profile = StudentProfile::find($attr['enrolled_student_id']);
                    if ($profile) {
                        return $profile->student_number;
                    }
                }
                return $attr['admission_student_id'] ?? null;
            },
            set: fn ($val) => ['admission_student_id' => $val]
        );
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AdmissionProgram::class, 'program_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function enrolledStudent(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'enrolled_student_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(ApplicationGuardian::class, 'application_id');
    }

    public function primaryGuardian(): HasOne
    {
        return $this->hasOne(ApplicationGuardian::class, 'application_id')->where('is_primary', true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AdmissionAssessment::class, 'application_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(AdmissionDecision::class, 'application_id');
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(AdmissionDecision::class, 'application_id')->latestOfMany();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id')->orderBy('created_at', 'desc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AdmissionPayment::class, 'application_id');
    }

    public function scopeByStatus(Builder $query, ApplicationStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeForProgram(Builder $query, string $programId): Builder
    {
        return $query->where('program_id', $programId);
    }

    /** QR is only meaningful once the council has approved the candidate. */
    public function qrIsValid(): bool
    {
        return !empty($this->qr_token)
            && $this->qr_expires_at !== null
            && $this->qr_expires_at->isFuture()
            && $this->qr_used_at === null
            && $this->withdrawn_at === null;
    }
}
