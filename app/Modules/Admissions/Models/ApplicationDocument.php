<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Enums\DocType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'application_documents';

    protected $fillable = [
        'application_id',
        'doc_type',
        'object_key',
        'file_name',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        // Aliases
        'original_filename',
        'storage_path',
        'storage_disk',
        'file_size_bytes',
        'file_hash',
        'doc_status',
        'review_notes',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function getDocTypeAttribute(): ?DocType
    {
        $val = $this->attributes['doc_type'] ?? null;
        return $val ? DocType::tryFrom($val) : null;
    }

    public function setDocTypeAttribute($value): void
    {
        $this->attributes['doc_type'] = $value instanceof DocType ? $value->value : (string) $value;
    }

    public function getDocStatusAttribute(): DocStatus
    {
        $val = $this->attributes['status'] ?? 'UPLOADED';
        return DocStatus::tryFrom($val) ?? DocStatus::UPLOADED;
    }

    public function setDocStatusAttribute($value): void
    {
        $this->attributes['status'] = $value instanceof DocStatus ? $value->value : (string) $value;
    }

    // Aliases
    protected function originalFilename(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['file_name'] ?? null,
            set: fn($v) => ['file_name' => $v]
        );
    }

    protected function storagePath(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['object_key'] ?? null,
            set: fn($v) => ['object_key' => $v]
        );
    }

    protected function storageDisk(): Attribute
    {
        return Attribute::make(
            get: fn() => 'local'
        );
    }

    protected function fileSizeBytes(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => (int) ($a['size_bytes'] ?? 0),
            set: fn($v) => ['size_bytes' => $v]
        );
    }

    protected function fileHash(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['checksum_sha256'] ?? null,
            set: fn($v) => ['checksum_sha256' => $v]
        );
    }

    protected function reviewNotes(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['rejection_reason'] ?? null,
            set: fn($v) => ['rejection_reason' => $v]
        );
    }

    protected function reviewedByUserId(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => $a['verified_by'] ?? null,
            set: fn($v) => ['verified_by' => $v]
        );
    }

    protected function reviewedAt(): Attribute
    {
        return Attribute::make(
            get: fn($v, $a) => isset($a['verified_at']) ? $this->asDateTime($a['verified_at']) : null,
            set: fn($v) => ['verified_at' => $v]
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
