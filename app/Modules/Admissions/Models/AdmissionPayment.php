<?php

namespace App\Modules\Admissions\Models;

use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Enums\PaymentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'admission_payments';

    protected $fillable = [
        'application_id',
        'payment_type',
        'amount',
        'currency',
        'provider',
        'transaction_ref',
        'payment_status',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'payment_type' => PaymentType::class,
        'payment_status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function gatewayLogs(): HasMany
    {
        return $this->hasMany(PaymentGatewayLog::class, 'payment_id');
    }

    public function isSuccess(): bool
    {
        return $this->payment_status === PaymentStatus::SUCCESS;
    }
}
