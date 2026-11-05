<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\CmsGalleryItem;
use App\Modules\Cms\Models\CmsNotice;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsStaffMember;
use App\Modules\Cms\Support\Sanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public, read-only JSON for the mobile apps and other clients. Only published content is returned. */
class CmsApiController extends Controller
{
    private function ok(mixed $data, string $message): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'meta' => ['timestamp' => now()->toIso8601String()], 'errors' => null]);
    }

    public function notices(): JsonResponse
    {
        return $this->ok(
            CmsNotice::live()->get()->map(fn ($n) => ['id' => $n->id, 'title' => $n->title, 'body' => $n->body, 'publish_on' => $n->publish_on?->toDateString(), 'expires_on' => $n->expires_on?->toDateString()]),
            'Notices retrieved.'
        );
    }

    public function gallery(): JsonResponse
    {
        return $this->ok(
            CmsGalleryItem::published()->get()->map(fn ($g) => ['id' => $g->id, 'title' => $g->title, 'category' => $g->category, 'image_url' => $g->image_url]),
            'Gallery retrieved.'
        );
    }

    public function staff(Request $request): JsonResponse
    {
        $request->validate(['group' => 'nullable|in:GOVERNING_BODY,TEACHER,STAFF']);

        return $this->ok(
            CmsStaffMember::published($request->group)->get()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'designation' => $m->designation, 'department' => $m->department, 'group' => $m->group, 'photo_url' => $m->photo_url]),
            'Staff retrieved.'
        );
    }

    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::published()->where('slug', $slug)->first();
        abort_unless($page, 404);

        return $this->ok(['slug' => $page->slug, 'title' => $page->title, 'body' => Sanitizer::html($page->body)], 'Page retrieved.');
    }
}
