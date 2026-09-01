<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Admission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'guardian_user_id',
        'application_no',
        'department',
        'ssc_board',
        'ssc_roll',
        'registration_number',
        'passing_year',
        'gpa',
        'photo_path',
        'name',
        'gender',
        'mobile',
        'email',
        'father_name',
        'mother_name',
        'guardian_name',
        'relation_with_guardian',
        'guardian_mobile',
        'present_address',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'pdf_path',
    ];

    protected $casts = [
        'gpa' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function guardianUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }

    public function admissionFeePayment(): ?PaymentTransaction
    {
        return $this->payments()
            ->where('type', 'admission_fee')
            ->latest()
            ->first();
    }
}
