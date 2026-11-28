<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Engine\Module;
use App\Modules\Campus\Engine\Portal;
use App\Modules\Campus\Engine\Records;
use App\Modules\Campus\Engine\Workflow;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Every record module for the app. The module file decides what each person sees and may do. */
class ModuleController extends Controller
{
    public function __construct(private Portal $portal, private Records $records)
    {
    }

    private function ok(array $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status);
    }

    private function studentId(Request $request): ?int
    {
        return $request->integer('student_id') ?: null;
    }

    private function describe(User $user, Module $m): array
    {
        $fields = $m->fields();
        $pick = fn (array $keys) => collect($keys)->map(fn ($k) => $k === 'staff_id'
            ? ['key' => 'staff_id', 'label' => $m->get('staff_field', 'Staff member'), 'type' => 'staff', 'required' => true, 'options' => []]
            : ($fields[$k] ?? null))->filter()->values()->all();
        $kind = Portal::kind($user);
        $join = $m->get('join');

        return [
            'key' => $m->key,
            'label' => $m->get('label'),
            'title' => $m->get('plural'),
            'icon' => $m->get('icon'),
            'group' => $m->group()['label'],
            'subject' => $m->subject(),
            'broadcast' => $m->has('broadcast'),
            'create_fields' => $pick($this->portal->creatable($user, $m)),
            'edit_fields' => $pick($kind === 'family' ? ($m->get('family.edit') ? $m->get('family.create', []) : []) : $m->get('teacher.update', [])),
            'status_options' => collect($m->get("{$kind}.status", []))->map(fn ($s) => ['value' => $s] + $m->statusLabel($s))->values(),
            'join' => $join && $kind === 'family' ? [
                'label' => $join['label'],
                'type' => $join['type'],
                'fields' => collect(Module::find($join['type'])->fields())->only(array_diff(Module::find($join['type'])->get('family.create', []), ['title']))->values(),
            ] : null,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return $this->ok(['modules' => Module::all()->filter(fn ($m) => $this->portal->visible($user, $m))->map(fn ($m) => $this->describe($user, $m))->values()]);
    }

    private function present(User $user, Module $module, object $record, ?array $names = null): array
    {
        // A mentor or supervisor sees their private notes; families never do.
        $private = Portal::kind($user) === 'teacher' && (int) $record->staff_id === (int) $user->id;

        return $this->records->present($module, $record, $private, $names);
    }

    public function records(Request $request, string $type)
    {
        $user = $request->user();
        $module = Module::find($type);
        $rows = $this->portal->query($user, $module, $this->studentId($request))->orderByDesc('id')->limit(80)->get()
            ->map(fn ($r) => $this->records->normalise($module, $r));
        $names = User::whereIn('id', $rows->pluck('student_id')->merge($rows->pluck('staff_id'))->filter()->unique())->pluck('name', 'id')->all();

        $joined = collect();
        if (($join = $module->get('join')) && Portal::kind($user) === 'family' && ($sid = $this->studentId($request) ?: ((int) $user->role_id === 7 ? $user->id : null))) {
            abort_unless(($s = User::find($sid)) && StudentContext::isFamily($user, $s), 404);
            $child = Module::find($join['type']);
            $joined = DB::table('campus_records')->where('type', $child->key)->where('student_id', $sid)->whereIn('parent_id', $rows->pluck('id'))->orderBy('id')->get()
                ->mapWithKeys(fn ($r) => [$r->parent_id => $child->statusLabel($r->status)]);
        }

        return $this->ok([
            'module' => $this->describe($user, $module),
            'records' => $rows->map(fn ($r) => $this->present($user, $module, $r, $names) + ['joined' => $joined[$r->id] ?? null])->values(),
        ]);
    }

    public function show(Request $request, string $type, int $record, Workflow $workflow)
    {
        $user = $request->user();
        $module = Module::find($type);
        $found = $this->portal->find($user, $module, $record, $this->studentId($request));
        $this->records->viewed($module, $found, $user);
        $flow = $module->has('workflow') ? $workflow->forRecord($module, $record) : null;

        return $this->ok([
            'module' => $this->describe($user, $module),
            'record' => $this->present($user, $module, $found),
            'raw' => collect($module->fields())->keys()->mapWithKeys(fn ($k) => [$k => $found->data[$k] ?? $found->{$k} ?? null])
                ->except($module->get('private_fields', [])),
            'approval' => $flow ? [
                'status' => $flow->status,
                'steps' => collect($flow->steps)->map(function ($step, $i) use ($flow) {
                    $action = $flow->actions->firstWhere('step', $i);

                    return [
                        'name' => $step === 'office' ? 'College office' : ucfirst($step),
                        'state' => $action ? $action->action : ($flow->status === 'pending' && $flow->step === $i ? 'waiting' : 'later'),
                        'note' => $action?->note,
                    ];
                })->values(),
            ] : null,
            'verify_url' => $module->get('verifiable') && !empty($found->data['code']) ? route('certificate.verify', ['code' => $found->data['code']]) : null,
        ]);
    }

    public function store(Request $request, string $type)
    {
        $module = Module::find($type);
        $record = $this->portal->create($request->user(), $module, $request->all());

        return $this->ok($this->present($request->user(), $module, $record), 201);
    }

    public function update(Request $request, string $type, int $record)
    {
        $module = Module::find($type);

        return $this->ok($this->present($request->user(), $module, $this->portal->change($request->user(), $module, $record, $request->all())));
    }

    public function join(Request $request, string $type, int $record)
    {
        $joined = $this->portal->join($request->user(), Module::find($type), $record, $request->all());

        return $this->ok($this->records->present(Module::find($joined->type), $joined), 201);
    }
}
