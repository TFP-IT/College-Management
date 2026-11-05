<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    use HasUuids;

    protected $table = 'cms_pages';

    protected $fillable = ['slug', 'title', 'body', 'is_published', 'updated_by'];

    protected $casts = ['is_published' => 'boolean'];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }
}
