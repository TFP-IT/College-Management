<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\URL;

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
        return $this->feePayment('application_fee');
    }

    public function admissionFeePayment(): ?PaymentTransaction
    {
        return $this->feePayment('admission_fee');
    }

    /** A paid transaction wins over any later unfinished attempt for the same fee. */
    private function feePayment(string $type): ?PaymentTransaction
    {
        return $this->payments()
            ->where(function ($q) use ($type) {
                $q->where('type', $type)
                  ->orWhereNotNull($type);
            })
            ->orderByRaw("status = 'paid' desc")
            ->latest()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * What to show for one fee. Until it is paid the amount is the configured one, because that is
     * what the next payment will charge.
     *
     * @return array{status:string, amount:float}
     */
    public function feeSummary(string $type): array
    {
        $payment = $this->feePayment($type);
        $status = $payment?->status ?? 'unpaid';

        return [
            'status' => $status,
            'amount' => $status === 'paid' ? (float) $payment->amount : (float) config('fees.' . $type),
        ];
    }

    /** A time-limited download link for the admission slip. */
    public function slipUrl(): string
    {
        return url(URL::temporarySignedRoute(
            'api.admission.slip',
            now()->addHours(config('security.slip_link_hours')),
            ['application_no' => $this->application_no],
            false,
        ));
    }

    /** The student, the linked guardian, or the parent of the student account. */
    public function isOwnedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ((int) $this->user_id === (int) $user->id || (int) $this->guardian_user_id === (int) $user->id) {
            return true;
        }

        return $this->user_id !== null
            && User::whereKey($this->user_id)->where('parent_id', $user->id)->exists();
    }
}
