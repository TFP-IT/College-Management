<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Cms\Models\CmsGalleryItem;
use App\Modules\Cms\Models\CmsNotice;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsStaffMember;
use App\Modules\Cms\Support\Sanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * One controller for the CMS resources. Each resource declares its model, label and form fields.
 * Admission programmes are edited here, but only their content: they cannot be created or deleted,
 * because their structure (class, group, eligibility) and their applications depend on them.
 */
class CmsAdminController extends Controller
{
    private const PROGRAMS = 'admission-programs';

    /** @return array<string, array> */
    private function resources(): array
    {
        return [
            'notices' => [
                'label' => 'Notices',
                'model' => CmsNotice::class,
                'fields' => ['title' => 'text', 'body' => 'textarea', 'publish_on' => 'date', 'expires_on' => 'date', 'is_published' => 'checkbox'],
                'columns' => ['title', 'publish_on', 'expires_on', 'is_published'],
            ],
            'gallery' => [
                'label' => 'Gallery',
                'model' => CmsGalleryItem::class,
                'fields' => ['title' => 'text', 'category' => 'text', 'image' => 'file', 'sort_order' => 'number', 'is_published' => 'checkbox'],
                'columns' => ['title', 'category', 'sort_order', 'is_published'],
            ],
            'staff' => [
                'label' => 'Governing body, teachers & staff',
                'model' => CmsStaffMember::class,
                'fields' => ['name' => 'text', 'designation' => 'text', 'department' => 'text', 'group' => 'select', 'photo' => 'file', 'sort_order' => 'number', 'is_published' => 'checkbox'],
                'columns' => ['name', 'designation', 'group', 'sort_order', 'is_published'],
            ],
            'pages' => [
                'label' => 'Editable pages',
                'model' => CmsPage::class,
                'fields' => ['title' => 'text', 'slug' => 'text', 'body' => 'html', 'is_published' => 'checkbox'],
                'columns' => ['title', 'slug', 'is_published'],
            ],
            self::PROGRAMS => [
                'label' => 'Admission programmes',
                'model' => AdmissionProgram::class,
                'fields' => [
                    'title' => 'text',
                    'description' => 'textarea',
                    'fee_application' => 'number',
                    'fee_admission' => 'number',
                    'seats' => 'number',
                    'application_opens_at' => 'datetime',
                    'application_deadline' => 'datetime',
                    'is_active' => 'checkbox',
                ],
                'columns' => ['title', 'seats', 'application_deadline', 'is_active'],
            ],
        ];
    }

    private function resource(string $key): array
    {
        $resources = $this->resources();
        abort_unless(isset($resources[$key]), 404);

        if ($key === self::PROGRAMS) {
            abort_unless(request()->user()?->can('admission.program.manage'), 403);
        }

        return $resources[$key];
    }

    /** Programmes are created by the seeder and never deleted from the CMS. */
    private function guardProgramsFromCreateOrDelete(string $key): void
    {
        abort_if($key === self::PROGRAMS, 403, 'Admission programmes are managed by the admissions team and cannot be created or deleted here.');
    }

    public function index()
    {
        $counts = [
            'notices' => CmsNotice::count(),
            'gallery' => CmsGalleryItem::count(),
            'staff' => CmsStaffMember::count(),
            'pages' => CmsPage::count(),
        ];
        $resources = $this->resources();
        if (!request()->user()?->can('admission.program.manage')) {
            unset($resources[self::PROGRAMS]);
        } else {
            $counts[self::PROGRAMS] = AdmissionProgram::count();
        }

        return view('cms.admin.index', ['resources' => $resources, 'counts' => $counts]);
    }

    public function list(string $key)
    {
        $res = $this->resource($key);

        return view('cms.admin.list', [
            'key' => $key,
            'res' => $res,
            'items' => $res['model']::query()->latest('created_at')->get(),
        ]);
    }

    public function create(string $key)
    {
        $this->guardProgramsFromCreateOrDelete($key);

        return view('cms.admin.form', ['key' => $key, 'res' => $this->resource($key), 'item' => null]);
    }

    public function store(Request $request, string $key, AuditService $audit)
    {
        $this->guardProgramsFromCreateOrDelete($key);
        $res = $this->resource($key);
        $data = $this->validated($request, $key, null);

        $item = $res['model']::create($data + ['created_by' => $request->user()?->id]);
        $audit->record("cms_{$key}_created", (string) $item->id, [], ['title' => $item->title ?? $item->name ?? null], $request->user(), 'cms');

        return redirect()->route('cms.admin.list', $key)->with('status', "{$res['label']}: item created.");
    }

    public function edit(string $key, string $id)
    {
        $res = $this->resource($key);
        $item = $res['model']::findOrFail($id);

        return view('cms.admin.form', ['key' => $key, 'res' => $res, 'item' => $item]);
    }

    public function update(Request $request, string $key, string $id, AuditService $audit)
    {
        $res = $this->resource($key);
        $item = $res['model']::findOrFail($id);
        $old = $item->only(array_keys($res['fields']));

        $item->update($this->validated($request, $key, $item));
        $audit->record("cms_{$key}_updated", (string) $item->id, $old, ['updated' => true], $request->user(), 'cms');

        return redirect()->route('cms.admin.list', $key)->with('status', "{$res['label']}: changes saved.");
    }

    public function destroy(Request $request, string $key, string $id, AuditService $audit)
    {
        $this->guardProgramsFromCreateOrDelete($key);
        $res = $this->resource($key);
        $item = $res['model']::findOrFail($id);

        foreach (['image_path', 'photo_path'] as $column) {
            if (!empty($item->{$column})) {
                Storage::disk('public')->delete($item->{$column});
            }
        }
        $item->delete();
        $audit->record("cms_{$key}_deleted", $id, [], [], $request->user(), 'cms');

        return redirect()->route('cms.admin.list', $key)->with('status', "{$res['label']}: item deleted.");
    }

    private function validated(Request $request, string $key, $item): array
    {
        $rules = match ($key) {
            'notices' => [
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:5000',
                'publish_on' => 'nullable|date',
                'expires_on' => 'nullable|date|after_or_equal:publish_on',
                'is_published' => 'nullable|boolean',
            ],
            'gallery' => [
                'title' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'image' => ($item ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png,webp|max:4096',
                'sort_order' => 'nullable|integer|min:0|max:9999',
                'is_published' => 'nullable|boolean',
            ],
            'staff' => [
                'name' => 'required|string|max:255',
                'designation' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'group' => ['required', Rule::in(CmsStaffMember::GROUPS)],
                'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'sort_order' => 'nullable|integer|min:0|max:9999',
                'is_published' => 'nullable|boolean',
            ],
            'pages' => [
                'title' => 'required|string|max:255',
                'slug' => ['required', 'regex:/^[a-z0-9-]+$/', 'max:100', Rule::unique('cms_pages', 'slug')->ignore($item?->id, 'id')],
                'body' => 'nullable|string|max:20000',
                'is_published' => 'nullable|boolean',
            ],
            self::PROGRAMS => [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'fee_application' => 'required|numeric|min:0|max:999999.99',
                'fee_admission' => 'required|numeric|min:0|max:999999.99',
                'seats' => 'required|integer|min:0|max:100000',
                'application_opens_at' => 'nullable|date',
                'application_deadline' => 'nullable|date|after_or_equal:application_opens_at',
                'is_active' => 'nullable|boolean',
            ],
        };

        $data = $request->validate($rules);

        // Each resource has one on/off switch: "published" for content, "active" for programmes.
        if ($key === self::PROGRAMS) {
            $data['is_active'] = $request->boolean('is_active');
            return $data;
        }

        $data['is_published'] = $request->boolean('is_published');

        if ($key === 'pages') {
            $data['body'] = Sanitizer::html($data['body'] ?? '');
            $data['updated_by'] = $request->user()?->id;
        }

        foreach (['image' => ['gallery', 'image_path'], 'photo' => ['staff', 'photo_path']] as $field => [$resourceKey, $column]) {
            if ($key === $resourceKey && $request->hasFile($field)) {
                if ($item && $item->{$column}) {
                    Storage::disk('public')->delete($item->{$column});
                }
                $data[$column] = $request->file($field)->store("cms/{$resourceKey}", 'public');
            }
            unset($data[$field]);
        }

        return $data;
    }
}
