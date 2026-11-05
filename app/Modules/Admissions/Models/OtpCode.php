<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'otp_codes';

    protected $fillable = [
        'mobile',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
        // Aliases
        'phone',
        'email',
        'verified_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['mobile'] ?? null,
            set: fn($v) => ['mobile' => $v]
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => null,
            set: fn($v) => []
        );
    }

    protected function verifiedAt(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => isset($a['consumed_at']) ? $this->asDateTime($a['consumed_at']) : null,
            set: fn($v) => ['consumed_at' => $v]
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->consumed_at !== null;
    }
}
