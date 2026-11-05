<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CmsStaffMember extends Model
{
    use HasUuids;

    public const GROUPS = ['GOVERNING_BODY', 'TEACHER', 'STAFF'];

    protected $table = 'cms_staff_members';

    protected $fillable = ['name', 'designation', 'department', 'group', 'photo_path', 'sort_order', 'is_published'];

    protected $casts = ['is_published' => 'boolean', 'sort_order' => 'integer'];

    protected $appends = ['photo_url'];

    public function scopePublished(Builder $q, ?string $group = null): Builder
    {
        return $q->where('is_published', true)
            ->when($group, fn ($w) => $w->where('group', $group))
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
