<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationGuardian extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'application_guardians';

    protected $fillable = [
        'application_id',
        'name',
        'relation',
        'mobile',
        'email',
        'occupation',
        'nid_passport',
        'is_primary',
        // Aliases
        'full_name',
        'phone',
    ];

    protected $casts = [
        'nid_passport' => 'encrypted',
        'is_primary' => 'boolean',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn($val, $attr) => $attr['name'] ?? null,
            set: fn($val) => ['name' => $val]
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn($val, $attr) => $attr['mobile'] ?? null,
            set: fn($val) => ['mobile' => $val]
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }
}
