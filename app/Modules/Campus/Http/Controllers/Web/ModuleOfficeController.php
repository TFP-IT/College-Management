<?php

namespace App\Modules\Campus\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Engine\Access;
use App\Modules\Campus\Engine\Effects;
use App\Modules\Campus\Engine\Module;
use App\Modules\Campus\Engine\Records;
use App\Modules\Campus\Engine\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** List, add, edit and decide records of any module, plus the shared approval inbox. */
class ModuleOfficeController extends Controller
{
    public function __construct(private Records $records, private Workflow $workflow)
    {
    }

    private function school(Request $request): int
    {
        return (int) ($request->user()->school_id ?: 1);
    }

    private function module(Request $request, string $type): Module
    {
        $module = Module::find($type);
        abort_unless(Access::manages($request->user(), $module), 403, 'Your role does not manage this.');

        return $module;
    }

    private function sections(int $school): array
    {
        return DB::table('sections')->join('classes', 'classes.id', '=', 'sections.class_id')->where('classes.school_id', $school)
            ->orderBy('classes.name')->orderBy('sections.name')
            ->get(['classes.id as c', 'sections.id as s', 'classes.name as class', 'sections.name as section'])
            ->mapWithKeys(fn ($r) => ["{$r->c}-{$r->s}" => "{$r->class} {$r->section}"])->all();
    }

    /** Public check of a certificate by its random code. Shows only what confirms it. */
    public function verifyCertificate(string $code)
    {
        $record = DB::table('campus_records')->where('type', 'credential')->where('data->code', $code)->first();
        abort_unless($record, 404, 'No certificate has that code.');

        return view('office.certificate', [
            'record' => $record,
            'data' => json_decode($record->data, true),
            'holder' => User::whereKey($record->student_id)->value('name'),
            'status' => Module::find('credential')->statusLabel($record->status),
        ]);
    }

    public function directory(Request $request)
    {
        $user = $request->user();
        $school = $this->school($request);
        $modules = Access::managed($user);
        $counts = DB::table('campus_records')->where('school_id', $school)->groupBy('type')->selectRaw('type, count(*) as c')->pluck('c', 'type');

        // Anything with an end date that is coming up soon.
        $expiring = $modules->filter(fn (Module $m) => $m->has('expiry_alert'))->flatMap(fn (Module $m) => $this->records->query($m, $school)
            ->whereNotNull('ends_at')->where('ends_at', '<=', now()->addDays($m->get('expiry_alert')))
            ->whereNotIn('status', ['done', 'ended', 'retired', 'closed'])->orderBy('ends_at')->limit(20)->get()
            ->map(fn ($r) => ['module' => $m, 'id' => $r->id, 'title' => $r->title, 'ends_at' => $r->ends_at]))->sortBy('ends_at')->values();

        return view('office.modules.directory', ['groups' => $modules->groupBy(fn (Module $m) => $m->group()['label']), 'counts' => $counts, 'expiring' => $expiring]);
    }

    public function index(Request $request, string $type)
    {
        $module = $this->module($request, $type);
        $school = $this->school($request);
        $status = (string) $request->query('status');
        $search = trim((string) $request->query('q'));
        $staffColumn = $module->table() ? 'user_id' : 'staff_id';

        $query = $this->records->query($module, $school)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($module, $search, $staffColumn) {
                $people = User::where('name', 'like', "%{$search}%")->orWhere('student_id', $search)->pluck('id');
                $q->where(function ($w) use ($module, $search, $staffColumn, $people) {
                    $w->whereIn($staffColumn, $people);
                    if (!$module->table()) {
                        $w->orWhereIn('student_id', $people)->orWhere('title', 'like', "%{$search}%");
                    }
                });
            });
        $page = $query->orderByDesc('id')->paginate(30)->withQueryString();
        $rows = collect($page->items())->map(fn ($r) => $this->records->normalise($module, $r));
        $names = User::whereIn('id', $rows->pluck('student_id')->merge($rows->pluck('staff_id'))->filter()->unique())->pluck('name', 'id')->all();

        return view('office.modules.index', [
            'module' => $module,
            'page' => $page,
            'rows' => $rows->map(fn ($r) => $this->records->present($module, $r, true, $names)),
            'status' => $status,
            'search' => $search,
        ]);
    }

    private function formData(Request $request, Module $module): array
    {
        $school = $this->school($request);

        return [
            'module' => $module,
            'students' => $module->subject() === 'student'
                ? User::where('school_id', $school)->where('role_id', 7)->where('status', 1)->orderBy('name')->limit(3000)->get(['id', 'name', 'student_id']) : collect(),
            'staff' => $module->subject() === 'staff' || $module->has('staff_field') ? $this->records->staffOptions($school) : [],
            'sections' => collect($module->fields())->contains('type', 'section') ? $this->sections($school) : [],
            'parents' => $module->has('parent_type') ? $this->records->query(Module::find($module->get('parent_type')), $school)->orderBy('title')->pluck('title', 'id') : collect(),
        ];
    }

    public function create(Request $request, string $type)
    {
        return view('office.modules.form', $this->formData($request, $this->module($request, $type)));
    }

    public function store(Request $request, string $type)
    {
        $module = $this->module($request, $type);
        $school = $this->school($request);
        $fields = $this->records->validate($module, $request->all(), $school);
        $links = $request->validate([
            'student_id' => ($module->subject() === 'student' ? 'required' : 'nullable') . '|integer',
            'staff_id' => ($module->subject() === 'staff' ? 'required' : 'nullable') . '|integer',
            'parent_id' => ($module->has('parent_type') ? 'required' : 'nullable') . '|integer',
        ]);

        // Linked ids must belong to this school.
        if (!empty($links['student_id'])) {
            abort_unless(User::where('id', $links['student_id'])->where('school_id', $school)->where('role_id', 7)->exists(), 404);
        }
        if (!empty($links['staff_id'])) {
            abort_unless(isset($this->records->staffOptions($school)[$links['staff_id']]), 404);
        }
        if (!empty($links['parent_id'])) {
            $this->records->find(Module::find($module->get('parent_type')), $school, (int) $links['parent_id']);
        }
        if (isset($fields['title']) && $module->fields()['title']['type'] === 'select') {
            $links['title'] = ucfirst($fields['title']);
        }

        $record = $this->records->create($module, $fields, $school, $request->user(), array_filter($links));

        return redirect()->route('office.module.show', [$module->key, $record->id])->with('ok', $module->get('label') . ' saved.');
    }

    public function show(Request $request, string $type, int $record, Effects $effects)
    {
        $module = Module::find($type);
        $user = $request->user();
        $flow = $module->has('workflow') ? $this->workflow->forRecord($module, $record) : null;
        $canAct = $flow && $this->workflow->canAct($user, $flow);
        // An approver may read the record they are being asked about.
        abort_unless(Access::manages($user, $module) || $canAct, 403, 'Your role does not manage this.');

        $found = $this->records->find($module, $this->school($request), $record);
        $this->records->viewed($module, $found, $user);

        $children = collect();
        if ($module->has('join') && !$module->table()) {
            $child = Module::find($module->get('join.type'));
            $children = $this->records->query($child, $found->school_id)->where('parent_id', $found->id)->orderByDesc('id')->limit(200)->get()
                ->map(fn ($r) => $this->records->present($child, $this->records->normalise($child, $r), true));
        }

        return view('office.modules.show', $this->formData($request, $module) + [
            'record' => $found,
            'view' => $this->records->present($module, $found, true),
            'manages' => Access::manages($user, $module),
            'flow' => $flow,
            'canAct' => $canAct,
            'children' => $children,
            'gaps' => $module->key === 'graduation_clearance' ? $effects->clearanceGaps($found) : null,
        ]);
    }

    public function update(Request $request, string $type, int $record)
    {
        $module = $this->module($request, $type);
        $found = $this->records->find($module, $this->school($request), $record);
        $data = $request->validate(['status' => 'required|string|max:30']);

        // A decision that belongs to the approval chain cannot be made by editing the status.
        if ($module->has('workflow')) {
            $pending = $this->workflow->forRecord($module, $record)?->status === 'pending';
            abort_if($data['status'] !== $found->status && ($pending || in_array($data['status'], ['approved', 'rejected'], true)), 422, 'Use the approval buttons to decide this request.');
        }

        $input = array_intersect_key($request->all(), $module->fields());
        if ($module->table()) {
            unset($input['period']);
        }
        $fields = $this->records->validate($module, $input, $found->school_id, array_keys($input), true);
        if ($fields) {
            $found = $this->records->update($module, $found, $fields, $request->user());
        }
        $this->records->setStatus($module, $found, $data['status'], $request->user());

        return back()->with('ok', 'Saved.');
    }

    public function approvals(Request $request)
    {
        $user = $request->user();

        return view('office.modules.approvals', [
            'waiting' => $this->workflow->inbox($user)->map(function ($r) {
                $r->module = Module::find($r->subject_type);
                $r->steps = json_decode($r->steps, true);

                return $r;
            }),
            'done' => DB::table('workflow_actions')->join('workflow_requests', 'workflow_requests.id', '=', 'workflow_actions.request_id')
                ->where('actor_id', $user->id)->orderByDesc('workflow_actions.id')->limit(20)
                ->get(['workflow_requests.title', 'workflow_requests.subject_type', 'workflow_requests.subject_id', 'workflow_actions.action', 'workflow_actions.created_at']),
            'names' => User::pluck('name', 'id'),
        ]);
    }

    public function decide(Request $request, int $approval)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'note' => 'nullable|string|max:500']);
        $outcome = $this->workflow->act($approval, $request->user(), $data['decision'] === 'approve', $data['note'] ?? null);

        return redirect()->route('office.approvals')->with('ok', [
            'approved' => 'Approved. The request is complete.',
            'rejected' => 'Not approved. The requester has been told.',
            'pending' => 'Approved. It now waits for the next person.',
        ][$outcome]);
    }
}
