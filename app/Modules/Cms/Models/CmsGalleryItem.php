<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CmsGalleryItem extends Model
{
    use HasUuids;

    protected $table = 'cms_gallery_items';

    protected $fillable = ['title', 'category', 'image_path', 'sort_order', 'is_published', 'created_by'];

    protected $casts = ['is_published' => 'boolean', 'sort_order' => 'integer'];

    protected $appends = ['image_url'];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
