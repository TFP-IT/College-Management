<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CmsNotice extends Model
{
    use HasUuids;

    protected $table = 'cms_notices';

    protected $fillable = ['title', 'body', 'publish_on', 'expires_on', 'is_published', 'created_by'];

    protected $casts = [
        'publish_on' => 'date',
        'expires_on' => 'date',
        'is_published' => 'boolean',
    ];

    /** Published, and inside the optional publish/expiry window. */
    public function scopeLive(Builder $q): Builder
    {
        $today = now()->toDateString();

        return $q->where('is_published', true)
            ->where(fn ($w) => $w->whereNull('publish_on')->orWhere('publish_on', '<=', $today))
            ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', $today))
            ->orderByDesc('publish_on')
            ->orderByDesc('created_at');
    }
}
