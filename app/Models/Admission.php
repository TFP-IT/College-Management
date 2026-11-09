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
        'photo_id',
        'name',
        'gender',
        'mobile',
        'email',
        'father_name',
        'mother_name',
        'guardian_name',
        'relation_with_guardian',
        'guardian_mobile',
        'guardian_nid',
        'guardian_occupation',
        'class_name',
        'birth_certificate_no',
        'date_of_birth',
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

    protected $appends = ['photo_url'];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_id');
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo_id && $this->photo) {
            return asset('storage/' . $this->photo->file_path);
        }
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        return null;
    }

    public function guardianUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }

    public function applicationFeePayment(): ?PaymentTransaction
    {
        return $this->payments()
            ->where(function ($q) {
                $q->where('type', 'application_fee')
                  ->orWhereNotNull('application_fee');
            })
            ->latest()
            ->first();
    }

    public function admissionFeePayment(): ?PaymentTransaction
    {
        return $this->payments()
            ->where(function ($q) {
                $q->where('type', 'admission_fee')
                  ->orWhereNotNull('admission_fee');
            })
            ->latest()
            ->first();
    }
}
