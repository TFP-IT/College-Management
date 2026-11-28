<?php

namespace App\Services;

use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Services\AttendanceService;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Services\TimetableService;
use App\Modules\Campus\Support\StudentContext;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The home page of each role in the school portal. Every figure is read from the signed-in person's own
 * school; a figure with nothing behind it is shown as a dash, never invented.
 *
 * Shape returned to resources/views/portal/dashboard.blade.php:
 *   role, actions[{label,url,icon}], kpis[{label,value,note,url,icon,tone,delta}], modules[...],
 *   rows[ [block, block, ...], ... ]  where a block is {type, title, subtitle?, url?, width: wide|narrow|full, ...}
 */
class PortalDashboard
{
    private const TZ = 'Asia/Dhaka';
    private const OFFSET = 6 * 3600;

    private User $user;
    private int $school;
    private ?int $session;

    public function __construct(
        private FinanceService $finance,
        private AttendanceService $attendance,
        private ResultService $results,
        private TimetableService $timetables,
        private TeacherClassroomService $classroom,
    ) {
    }

    public function build(User $user): array
    {
        $this->user = $user;
        $this->school = (int) $user->school_id;
        $this->session = $this->school ? StudentContext::runningSession($this->school) : null;

        $page = match ((int) $user->role_id) {
            1 => $this->superadmin(),
            2 => $this->admin(),
            3 => $this->teacher(),
            4 => $this->accountant(),
            5 => $this->librarian(),
            6 => $this->guardian(),
            7 => $this->student(),
            default => [],
        };

        return $page + ['role' => 'Member', 'actions' => [], 'kpis' => [], 'modules' => [], 'rows' => []];
    }

    // ---- Building blocks ----------------------------------------------------

    /** A link only when the page exists, so a missing route never breaks the dashboard. */
    private function url(string $route, mixed $parameters = []): ?string
    {
        return Route::has($route) ? route($route, $parameters) : null;
    }

    private function kpi(string $label, mixed $value, string $icon, string $tone, string $note = '', ?string $url = null, ?array $delta = null): array
    {
        return compact('label', 'value', 'icon', 'tone', 'note', 'url', 'delta');
    }

    private function actions(array $items): array
    {
        return collect($items)->map(fn ($item, $label) => ['label' => $label, 'url' => $this->url($item[0]), 'icon' => $item[1]])
            ->filter(fn ($a) => $a['url'])->values()->all();
    }

    private function money(int|float $amount): string
    {
        return '৳' . number_format($amount);
    }

    private function prefix(): string
    {
        return [2 => 'admin', 3 => 'teacher', 4 => 'accountant', 5 => 'librarian', 6 => 'parent', 7 => 'student'][(int) $this->user->role_id] ?? 'admin';
    }

    private function people(int $role): int
    {
        return DB::table('users')->where('school_id', $this->school)->where('role_id', $role)->count();
    }

    /** Change against the previous figure, as a percentage, or null when there is nothing to compare. */
    private function delta(float $now, float $before, string $period): ?array
    {
        if ($before <= 0) {
            return null;
        }
        $change = round(($now - $before) / $before * 100);

        return ['value' => ($change > 0 ? '+' : '') . $change . '%', 'up' => $change >= 0, 'note' => "vs {$period}"];
    }

    private function date(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : (strtotime((string) $value) ?: null);
    }

    private function events(): array
    {
        $items = DB::table('frontend_events')->where('school_id', $this->school)->where('status', 1)
            ->where('timestamp', '>=', now()->startOfDay()->timestamp)->orderBy('timestamp')->limit(4)->get()
            ->map(fn ($e) => ['title' => $e->title, 'note' => date('l, j M Y', (int) $e->timestamp), 'day' => date('j', (int) $e->timestamp), 'month' => date('M', (int) $e->timestamp)])->all();

        return ['type' => 'list', 'width' => 'narrow', 'title' => 'Upcoming events', 'url' => $this->url($this->prefix() . '.events.list'), 'empty' => 'No events are scheduled.', 'items' => $items];
    }

    private function notices(): array
    {
        $items = DB::table('noticeboard')->where('school_id', $this->school)->orderByDesc('id')->limit(4)->get()->map(function ($n) {
            $starts = $this->date($n->start_date);

            return [
                'title' => $n->notice_title,
                'note' => Str::limit(trim(strip_tags((string) $n->notice)), 80),
                'day' => $starts ? date('j', $starts) : null,
                'month' => $starts ? date('M', $starts) : null,
            ];
        })->all();

        return ['type' => 'list', 'width' => 'narrow', 'title' => 'Noticeboard', 'url' => $this->url($this->prefix() . '.noticeboard.list'), 'empty' => 'No notices yet.', 'items' => $items];
    }

    /**
     * Share of marked students who came in (late counts as present), per school day, oldest first.
     *
     * @param  array<int>|null  $students  only these students
     * @param  array<array{0:int,1:int}>|null  $sections  only these class/section pairs
     */
    private function attendanceTrend(int $days = 14, ?array $students = null, ?array $sections = null): array
    {
        $from = now(self::TZ)->subDays($days * 2)->startOfDay()->timestamp;
        $day = 'FLOOR((timestamp + ' . self::OFFSET . ') / 86400)';
        $rows = DB::table('daily_attendances')->where('school_id', $this->school)->where('timestamp', '>=', $from)
            ->when($students !== null, fn ($q) => $q->whereIn('student_id', $students ?: [0]))
            ->when($sections !== null, fn ($q) => $q->where(function ($q) use ($sections) {
                foreach ($sections ?: [[0, 0]] as [$class, $section]) {
                    $q->orWhere(fn ($q) => $q->where('class_id', $class)->where('section_id', $section));
                }
            }))
            ->selectRaw("{$day} as d, count(*) as total, sum(case when status in (1, 2) then 1 else 0 end) as came")
            ->groupBy('d')->orderByDesc('d')->limit($days)->get()->reverse()->values();

        return $rows->map(fn ($r) => [
            'label' => CarbonImmutable::createFromTimestampUTC((int) $r->d * 86400)->format('j M'),
            'value' => (int) round($r->came / max(1, $r->total) * 100),
            'note' => "{$r->came} of {$r->total} present",
        ])->all();
    }

    /** One student's share of school days present, per month, oldest first. A single day is too spiky to read. */
    private function attendanceByMonth(int $studentId, int $months = 6): array
    {
        $start = now(self::TZ)->startOfMonth()->subMonths($months - 1);
        $month = "DATE_FORMAT(FROM_UNIXTIME(timestamp + " . self::OFFSET . ", '%Y-%m-%d'), '%Y-%m')";
        $rows = DB::table('daily_attendances')->where('student_id', $studentId)->where('timestamp', '>=', $start->timestamp)
            ->selectRaw("{$month} as m, count(*) as total, sum(case when status in (1, 2) then 1 else 0 end) as came")->groupBy('m')->get()->keyBy('m');

        return collect(range(0, $months - 1))->map(fn ($i) => $start->copy()->addMonths($i))
            ->filter(fn ($m) => isset($rows[$m->format('Y-m')]))
            ->map(fn ($m) => [
                'label' => $m->format('M'),
                'value' => (int) round($rows[$m->format('Y-m')]->came / max(1, $rows[$m->format('Y-m')]->total) * 100),
                'note' => $rows[$m->format('Y-m')]->came . ' of ' . $rows[$m->format('Y-m')]->total . ' days, ' . $m->format('F'),
            ])->values()->all();
    }

    /** Payments recorded through the college ledger, per month, oldest first. */
    private function collectionByMonth(int $months = 6, ?array $students = null): array
    {
        $start = now(self::TZ)->startOfMonth()->subMonths($months - 1);
        $sums = DB::table('fee_payments')->where('school_id', $this->school)->where('status', 'confirmed')->where('paid_at', '>=', $start)
            ->when($students !== null, fn ($q) => $q->whereIn('student_id', $students ?: [0]))
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as m, sum(amount) as total")->groupBy('m')->pluck('total', 'm');

        return collect(range(0, $months - 1))->map(function ($i) use ($start, $sums) {
            $month = $start->copy()->addMonths($i);

            return ['label' => $month->format('M'), 'value' => (int) ($sums[$month->format('Y-m')] ?? 0), 'note' => $month->format('F Y')];
        })->all();
    }

    private function collectedIn(Carbon $month): int
    {
        return (int) DB::table('fee_payments')->where('school_id', $this->school)->where('status', 'confirmed')
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('amount');
    }

    private function invoices(?array $students = null): Collection
    {
        return $this->finance->invoices(StudentFeeManager::where('school_id', $this->school)
            ->when($students !== null, fn ($q) => $q->whereIn('student_id', $students ?: [0]))->get());
    }

    private function invoiceDonut(Collection $invoices, string $title = 'Invoices'): array
    {
        $groups = [
            ['Paid', 'paid', 'ok'], ['Part paid', 'partial', 'gold'], ['Being checked', 'verifying', 'info'], ['Due', 'due', 'crest'],
        ];

        return [
            'type' => 'donut', 'width' => 'narrow', 'title' => $title, 'subtitle' => 'By payment status',
            'center' => $invoices->count(), 'centerLabel' => $invoices->count() === 1 ? 'invoice' : 'invoices',
            'empty' => 'No invoices yet.',
            'segments' => collect($groups)->map(fn ($g) => [
                'label' => $g[0], 'tone' => $g[2], 'value' => $invoices->where('status', $g[1])->count(),
                'note' => $this->money($g[1] === 'paid' ? $invoices->where('status', 'paid')->sum('total') : $invoices->where('status', $g[1])->sum('due')),
            ])->filter(fn ($s) => $s['value'] > 0)->values()->all(),
        ];
    }

    private function recentPayments(int $limit = 6, ?array $students = null, bool $receipts = false): array
    {
        $rows = DB::table('fee_payments')->where('fee_payments.school_id', $this->school)->where('fee_payments.status', 'confirmed')
            ->when($students !== null, fn ($q) => $q->whereIn('fee_payments.student_id', $students ?: [0]))
            ->leftJoin('users', 'users.id', '=', 'fee_payments.student_id')
            ->leftJoin('student_fee_managers as i', 'i.id', '=', 'fee_payments.invoice_id')
            ->orderByDesc('fee_payments.paid_at')->orderByDesc('fee_payments.id')->limit($limit)
            ->get(['fee_payments.id', 'fee_payments.amount', 'fee_payments.method', 'fee_payments.receipt_no', 'fee_payments.paid_at', 'users.name', 'i.title']);
        $methods = ['cash' => 'Cash', 'bank' => 'Bank', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'card' => 'Card', 'online' => 'Online'];

        return $rows->map(fn ($p) => [
            ['text' => $p->name ?? '-', 'avatar' => $p->name ?? '?', 'sub' => $p->receipt_no, 'url' => $receipts ? $this->url('office.receipt', $p->id) : null],
            ['text' => $p->title ?? 'Fee payment'],
            ['badge' => $methods[$p->method] ?? ucfirst((string) $p->method), 'tone' => 'neutral'],
            ['text' => Carbon::parse($p->paid_at)->timezone(self::TZ)->format('j M, g:i A')],
            ['text' => $this->money($p->amount), 'strong' => true, 'num' => true],
        ])->all();
    }

    /**
     * The office portal's home page. It shows only what the person's office areas allow.
     *
     * @param  string[]  $areas  from OfficeAccess::areas()
     * @param  array  $today  OfficeReport::today()
     * @param  array|null  $money  OfficeReport::money(), when the person handles fees
     */
    public function office(User $user, array $areas, array $today, ?array $money): array
    {
        $this->user = $user;
        $this->school = (int) $user->school_id;
        $this->session = $this->school ? StudentContext::runningSession($this->school) : null;
        $can = fn (string $area) => in_array($area, $areas, true);
        $roles = \App\Modules\Campus\Engine\Access::roles($user);
        $role = in_array('Principal', $roles, true) ? 'Principal' : (in_array('admin', $roles, true) ? 'Office administrator' : (in_array('accounts', $roles, true) ? 'Accounts' : 'Office'));
        $rate = $today['recorded'] ? (int) round($today['attended'] / $today['recorded'] * 100) : null;
        $approvals = DB::table('workflow_requests')->where('school_id', $this->school)->where('status', 'pending')->count();
        $invoices = $money ? $this->invoices() : collect();

        $kpis = [
            $this->kpi('Attendance today', $rate === null ? '-' : "{$rate}%", 'check', 'ok', $today['recorded'] ? "{$today['attended']} of {$today['recorded']} marked, {$today['late']} late" : 'Not taken yet'),
            $this->kpi('Students enrolled', $today['students'], 'users', 'crest', trans_choice('{1} :count section|[0,*] :count sections', $n = $today['classes']->count(), ['count' => $n]), $can('students') ? $this->url('office.students') : null),
        ];
        if ($money) {
            $kpis[] = $this->kpi('Collected today', $this->money($money['today']), 'wallet', 'gold', $this->money($money['month']) . ' this month', $this->url('office.fees'));
            $kpis[] = $this->kpi('Outstanding fees', $this->money($money['due']), 'alert', 'info', "{$money['debtors']} students with a balance", $this->url('office.fees'));
        } else {
            $kpis[] = $this->kpi('Results to approve', $today['pending_results'], 'award', 'gold', 'Families see marks after you publish', $can('results') ? $this->url('office.results') : null);
            $kpis[] = $this->kpi('Help requests', $today['open_tickets'], 'chat', 'info', 'Open or in progress', $can('help') ? $this->url('office.tickets') : null);
        }

        $modules = array_values(array_filter([
            ['label' => 'Attendance', 'value' => $today['unmarked_sections'], 'unit' => 'sections not marked', 'chip' => $today['unmarked_sections'] ? 'Ask the class teacher' : 'All marked', 'tone' => $today['unmarked_sections'] ? 'gold' : 'ok', 'icon' => 'check', 'url' => null],
            $can('results') ? ['label' => 'Results', 'value' => $today['pending_results'], 'unit' => 'waiting', 'chip' => 'For approval', 'tone' => 'info', 'icon' => 'award', 'url' => $this->url('office.results')] : null,
            $can('approvals') ? ['label' => 'Approvals', 'value' => $approvals, 'unit' => 'pending', 'chip' => 'Requests', 'tone' => 'crest', 'icon' => 'stamp', 'url' => $this->url('office.approvals')] : null,
            $can('help') ? ['label' => 'Help desk', 'value' => $today['open_tickets'], 'unit' => 'open', 'chip' => 'Requests', 'tone' => 'info', 'icon' => 'chat', 'url' => $this->url('office.tickets')] : null,
            $money ? ['label' => 'Fees', 'value' => $money['debtors'], 'unit' => 'with dues', 'chip' => $invoices->where('status', 'verifying')->count() . ' to verify', 'tone' => 'gold', 'icon' => 'receipt', 'url' => $this->url('office.fees')] : null,
            $can('insights') ? ['label' => 'Low attendance', 'value' => count($today['at_risk']), 'unit' => 'students', 'chip' => 'Below 80%', 'tone' => count($today['at_risk']) ? 'crest' : 'ok', 'icon' => 'alert', 'url' => $this->url('office.insights')] : null,
        ]));

        $sections = [
            'type' => 'meters', 'width' => 'narrow', 'title' => 'Attendance by section', 'subtitle' => 'Today', 'empty' => 'No enrolled students yet.',
            'items' => $today['classes']->map(fn ($c) => [
                'label' => $c['label'],
                'percent' => $c['students'] ? (int) round(($c['present'] + $c['late']) / $c['students'] * 100) : 0,
                'value' => $c['recorded'] ? "{$c['recorded']} of {$c['students']} marked" : 'Not marked',
                'tone' => $c['recorded'] < $c['students'] ? 'gold' : 'ok',
            ])->values()->all(),
        ];
        $trend = ['type' => 'area', 'width' => 'wide', 'title' => 'Attendance', 'subtitle' => 'Share of marked students present, last school days', 'suffix' => '%', 'max' => 100, 'items' => $this->attendanceTrend(), 'empty' => 'No attendance has been recorded yet.'];
        $risk = [
            'type' => 'table', 'width' => 'narrow', 'compact' => true, 'title' => 'Low attendance', 'subtitle' => 'Below 80% across recorded days', 'url' => $can('insights') ? $this->url('office.insights') : null,
            'empty' => 'No student is below 80%.', 'head' => ['Student', 'Rate'],
            'rows' => collect($today['at_risk'])->map(fn ($r) => [
                ['text' => $r->name, 'avatar' => $r->name, 'sub' => $r->student_id, 'url' => $can('students') ? $this->url('office.student', $r->id) : null],
                ['badge' => $r->rate . '%', 'tone' => 'crest'],
            ])->all(),
        ];

        $rows = $money ? [
            [$trend, $this->invoiceDonut($invoices, 'Fee invoices')],
            [['type' => 'bars', 'width' => 'wide', 'title' => 'Fee collection', 'subtitle' => 'Payments recorded, last six months', 'items' => $this->collectionByMonth(), 'money' => true, 'url' => $this->url('office.fees')], $sections],
            [['type' => 'table', 'width' => 'wide', 'title' => 'Recent payments', 'url' => $this->url('office.fees'), 'empty' => 'No payment has been recorded yet.', 'head' => ['Student', 'For', 'Method', 'Received', 'Amount'], 'rows' => $this->recentPayments(6, null, true)], $risk],
        ] : [
            [$trend, $sections],
            [$risk, $this->notices()],
        ];

        return [
            'role' => $role,
            'actions' => array_values(array_filter([
                $can('announcements') ? ['label' => 'Send an announcement', 'url' => $this->url('office.announcements'), 'icon' => 'chat'] : null,
                $money ? ['label' => 'Collect fees', 'url' => $this->url('office.fees'), 'icon' => 'wallet'] : null,
                $can('results') ? ['label' => 'Review results', 'url' => $this->url('office.results'), 'icon' => 'award'] : null,
                $can('students') ? ['label' => 'Students', 'url' => $this->url('office.students'), 'icon' => 'users'] : null,
            ])),
            'kpis' => $kpis,
            'modules' => $modules,
            'rows' => $rows,
        ];
    }

    // ---- Roles --------------------------------------------------------------

    private function superadmin(): array
    {
        $year = now()->year;
        $payments = DB::table('subscriptions')->whereBetween('date_added', [now()->startOfYear()->timestamp, now()->endOfYear()->timestamp])->get(['paid_amount', 'date_added']);
        $months = collect(range(1, 12))->map(fn ($m) => [
            'label' => date('M', mktime(0, 0, 0, $m, 1)),
            'value' => (float) $payments->filter(fn ($p) => (int) date('n', (int) $p->date_added) === $m)->sum('paid_amount'),
            'note' => date('F', mktime(0, 0, 0, $m, 1)) . " {$year}",
        ]);
        $active = DB::table('subscriptions')->where('expire_date', '>=', time())->count();
        $expired = DB::table('subscriptions')->where('expire_date', '<', time())->where('expire_date', '>', 0)->count();
        $lifetime = DB::table('subscriptions')->where('expire_date', 0)->count();
        $expiry = DB::table('subscriptions')->orderByDesc('id')->get(['school_id', 'expire_date'])->unique('school_id')->keyBy('school_id');

        return [
            'role' => 'Super admin',
            'actions' => $this->actions(['Add a school' => ['superadmin.school.add', 'plus'], 'Packages' => ['superadmin.package', 'layers']]),
            'kpis' => [
                $this->kpi('Schools', DB::table('schools')->count(), 'school', 'crest', 'Registered on this system', $this->url('superadmin.school.list')),
                $this->kpi('Active subscriptions', $active, 'check', 'ok', 'Not yet expired', $this->url('superadmin.subscription.report')),
                $this->kpi('Received this year', '৳' . number_format($months->sum('value')), 'wallet', 'gold', "Subscription payments, {$year}", $this->url('superadmin.subscription.report')),
                $this->kpi('Waiting for review', DB::table('payment_history')->where('paid_by', 'offline')->where('status', 'pending')->count(), 'clock', 'info', 'Offline payment requests', $this->url('superadmin.subscription.pending')),
            ],
            'rows' => [
                [
                    ['type' => 'bars', 'width' => 'wide', 'title' => 'Subscription payments', 'subtitle' => "Each month of {$year}", 'items' => $months->all(), 'money' => true],
                    ['type' => 'donut', 'width' => 'narrow', 'title' => 'Subscriptions', 'subtitle' => 'By state', 'center' => $active + $expired + $lifetime, 'centerLabel' => 'in total', 'empty' => 'No subscriptions yet.',
                        'segments' => array_values(array_filter([
                            ['label' => 'Active', 'tone' => 'ok', 'value' => $active, 'note' => ''],
                            ['label' => 'Lifetime', 'tone' => 'gold', 'value' => $lifetime, 'note' => ''],
                            ['label' => 'Expired', 'tone' => 'crest', 'value' => $expired, 'note' => ''],
                        ], fn ($s) => $s['value'] > 0))],
                ],
                [[
                    'type' => 'table', 'width' => 'full', 'title' => 'Schools', 'url' => $this->url('superadmin.school.list'), 'empty' => 'No school is registered yet.',
                    'head' => ['School', 'Phone', 'Subscription', 'Status'],
                    'rows' => DB::table('schools')->orderBy('id')->limit(10)->get()->map(fn ($s) => [
                        ['text' => $s->title, 'avatar' => $s->title, 'sub' => $s->email],
                        ['text' => $s->phone ?: '-'],
                        ['text' => isset($expiry[$s->id]) ? ((int) $expiry[$s->id]->expire_date === 0 ? 'Lifetime' : 'Until ' . date('j M Y', (int) $expiry[$s->id]->expire_date)) : 'None'],
                        ['badge' => (int) $s->status === 1 ? 'Active' : 'Inactive', 'tone' => (int) $s->status === 1 ? 'ok' : 'neutral'],
                    ])->all(),
                ]],
            ],
        ];
    }

    private function admin(): array
    {
        $enrolled = DB::table('enrollments')->where('school_id', $this->school)->when($this->session, fn ($q) => $q->where('session_id', $this->session));
        $enrolledCount = (clone $enrolled)->count();
        $byClass = (clone $enrolled)->selectRaw('class_id, count(*) as c')->groupBy('class_id')->pluck('c', 'class_id');
        $start = now(self::TZ)->startOfDay()->timestamp;
        $today = DB::table('daily_attendances')->where('school_id', $this->school)->whereBetween('timestamp', [$start, $start + 86399])
            ->get(['class_id', 'student_id', 'status'])->unique('student_id');
        $present = $today->whereIn('status', [1, 2])->count();
        $classes = DB::table('classes')->where('school_id', $this->school)->orderBy('id')->pluck('name', 'id');
        $invoices = $this->invoices();
        $due = $invoices->sum('due');
        $thisMonth = $this->collectedIn(now(self::TZ));
        $lastMonth = $this->collectedIn(now(self::TZ)->subMonthNoOverflow());
        $staff = $this->people(2) + $this->people(3) + $this->people(4) + $this->people(5);

        $approvals = DB::table('workflow_requests')->where('school_id', $this->school)->where('status', 'pending')->count();
        $results = DB::table('result_publications')->where('school_id', $this->school)->where('status', 'submitted')->count();
        $tickets = DB::table('service_tickets')->where('school_id', $this->school)->whereNotIn('status', ['resolved', 'closed'])->count();
        $verifying = $invoices->where('status', 'verifying')->count();
        $loans = Schema::hasTable('library_loans') ? DB::table('library_loans')->where('school_id', $this->school)->where('status', 'issued') : null;

        return [
            'role' => 'Administrator',
            'actions' => $this->actions(['Office portal' => ['office.dashboard', 'grid'], 'Take attendance' => ['admin.daily_attendance', 'check'], 'Students' => ['admin.student', 'users']]),
            'kpis' => [
                $this->kpi('Students', $enrolledCount, 'users', 'crest', $this->people(7) . ' student accounts', $this->url('admin.student')),
                $this->kpi('Staff', $staff, 'briefcase', 'info', $this->people(3) . ' teachers', $this->url('admin.teacher')),
                $this->kpi('Attendance today', $today->isEmpty() ? '-' : round($present / $today->count() * 100) . '%', 'check', 'ok', $today->isEmpty() ? 'Not taken yet' : "{$present} of {$today->count()} marked students", $this->url('admin.daily_attendance')),
                $this->kpi('Collected this month', $this->money($thisMonth), 'wallet', 'gold', $this->money($due) . ' still outstanding', $this->url('admin.fee_manager.list'), $this->delta($thisMonth, $lastMonth, 'last month')),
            ],
            'modules' => array_values(array_filter([
                ['label' => 'Attendance', 'value' => $present, 'unit' => 'present', 'chip' => ($today->count() - $present) . ' absent', 'tone' => 'ok', 'icon' => 'check', 'url' => $this->url('admin.daily_attendance')],
                ['label' => 'Fees', 'value' => $invoices->where('due', '>', 0)->count(), 'unit' => 'open invoices', 'chip' => $verifying . ' to verify', 'tone' => 'gold', 'icon' => 'receipt', 'url' => $this->url('office.fees')],
                ['label' => 'Results', 'value' => $results, 'unit' => 'waiting', 'chip' => 'For approval', 'tone' => 'info', 'icon' => 'award', 'url' => $this->url('office.results')],
                ['label' => 'Approvals', 'value' => $approvals, 'unit' => 'pending', 'chip' => 'Requests', 'tone' => 'crest', 'icon' => 'stamp', 'url' => $this->url('office.approvals')],
                ['label' => 'Help desk', 'value' => $tickets, 'unit' => 'open', 'chip' => 'Requests', 'tone' => 'info', 'icon' => 'chat', 'url' => $this->url('office.tickets')],
                $loans ? ['label' => 'Library', 'value' => (clone $loans)->count(), 'unit' => 'on loan', 'chip' => (clone $loans)->where('due_on', '<', now()->toDateString())->count() . ' overdue', 'tone' => 'ok', 'icon' => 'book', 'url' => $this->url('office.library')] : null,
            ])),
            'rows' => [
                [
                    ['type' => 'area', 'width' => 'wide', 'title' => 'Attendance', 'subtitle' => 'Share of marked students present, last school days', 'suffix' => '%', 'max' => 100, 'items' => $this->attendanceTrend(), 'url' => $this->url('admin.daily_attendance'), 'empty' => 'No attendance has been recorded yet.'],
                    $this->invoiceDonut($invoices, 'Fee invoices'),
                ],
                [
                    ['type' => 'bars', 'width' => 'wide', 'title' => 'Fee collection', 'subtitle' => 'Payments recorded, last six months', 'items' => $this->collectionByMonth(), 'money' => true, 'url' => $this->url('office.fees')],
                    [
                        'type' => 'meters', 'width' => 'narrow', 'title' => "Today's attendance", 'subtitle' => 'By class', 'url' => $this->url('admin.daily_attendance'), 'empty' => 'No classes are set up.',
                        'items' => $classes->map(function ($name, $id) use ($today, $byClass) {
                            $marked = $today->where('class_id', $id);
                            $in = $marked->whereIn('status', [1, 2])->count();
                            $total = (int) ($byClass[$id] ?? 0);

                            return ['label' => $name, 'percent' => $total ? round($in / $total * 100) : 0, 'value' => $marked->isEmpty() ? 'Not marked' : "{$in} of {$total}", 'tone' => 'ok'];
                        })->values()->take(8)->all(),
                    ],
                ],
                [
                    ['type' => 'table', 'width' => 'wide', 'title' => 'Recent payments', 'url' => $this->url('office.fees'), 'empty' => 'No payment has been recorded yet.', 'head' => ['Student', 'For', 'Method', 'Received', 'Amount'], 'rows' => $this->recentPayments()],
                    $this->notices(),
                ],
            ],
        ];
    }

    private function teacher(): array
    {
        $classes = $this->classroom->classes($this->user);
        $week = $this->timetables->week(['teacher_id' => $this->user->id, 'school_id' => $this->school, 'session_id' => $this->session]);
        $periods = collect($week)->firstWhere('day', TimetableService::todayKey())['periods'] ?? [];
        $pairs = $classes->map(fn ($c) => [$c['class_id'], $c['section_id']])->unique(fn ($p) => "{$p[0]}-{$p[1]}")->values();
        $students = $classes->unique(fn ($c) => "{$c['class_id']}-{$c['section_id']}")->sum('students');
        $toMark = DB::table('assignment_submissions')->whereNull('graded_at')
            ->whereIn('assignment_id', DB::table('teacher_assignments')->where('teacher_id', $this->user->id)->select('id'))->count();
        $trend = $this->attendanceTrend(10, null, $pairs->all());

        return [
            'role' => 'Teacher',
            'actions' => $this->actions(['Take attendance' => ['teacher.daily_attendance', 'check'], 'Enter marks' => ['teacher.marks', 'award'], 'Class routine' => ['teacher.routine', 'calendar']]),
            'kpis' => [
                $this->kpi('Periods today', count($periods), 'clock', 'crest', count($periods) ? 'On your routine' : 'No class today', $this->url('teacher.routine')),
                $this->kpi('My sections', $pairs->count(), 'layers', 'info', trans_choice('{1} :count subject|[0,*] :count subjects', $n = $classes->pluck('subject_id')->filter()->unique()->count(), ['count' => $n])),
                $this->kpi('My students', $students, 'users', 'ok', 'In those sections'),
                $this->kpi('Work to mark', $toMark, 'edit', 'gold', 'Handed in, not marked yet'),
            ],
            'rows' => [
                [
                    ['type' => 'periods', 'width' => 'wide'],
                    ['type' => 'area', 'width' => 'narrow', 'title' => 'Attendance in my classes', 'subtitle' => 'Last school days', 'suffix' => '%', 'max' => 100, 'items' => $trend, 'empty' => 'No attendance recorded in your sections yet.'],
                ],
                [
                    [
                        'type' => 'table', 'width' => 'wide', 'title' => 'My classes', 'url' => $this->url('teacher.subject_list'), 'empty' => 'No class is assigned to you yet.',
                        'head' => ['Subject', 'Section', 'Students', 'Syllabus covered'],
                        'rows' => $classes->map(fn ($c) => [
                            ['text' => $c['name'], 'strong' => true],
                            ['text' => $c['section']],
                            ['text' => (string) $c['students'], 'num' => true],
                            ['meter' => (int) $c['progress']],
                        ])->all(),
                    ],
                    $this->notices(),
                ],
            ],
        ];
    }

    private function accountant(): array
    {
        $invoices = $this->invoices();
        $open = $invoices->where('due', '>', 0);
        $thisMonth = $this->collectedIn(now(self::TZ));
        $lastMonth = $this->collectedIn(now(self::TZ)->subMonthNoOverflow());
        $expenses = (int) DB::table('expenses')->where('school_id', $this->school)->whereBetween('date', [now()->startOfMonth()->timestamp, now()->endOfMonth()->timestamp])->sum('amount');
        $names = User::whereIn('id', $open->pluck('student_id'))->get(['id', 'name', 'student_id'])->keyBy('id');

        return [
            'role' => 'Accounts',
            'actions' => $this->actions(['Collect fees' => ['office.fees', 'wallet'], 'Fee manager' => ['accountant.fee_manager.list', 'receipt'], 'Expenses' => ['accountant.expense.list', 'chart']]),
            'kpis' => [
                $this->kpi('Collected this month', $this->money($thisMonth), 'wallet', 'ok', now()->format('F Y'), $this->url('office.fees'), $this->delta($thisMonth, $lastMonth, 'last month')),
                $this->kpi('Outstanding', $this->money($open->sum('due')), 'alert', 'crest', $open->count() . ' open invoices', $this->url('office.fees')),
                $this->kpi('To verify', $invoices->where('status', 'verifying')->count(), 'clock', 'info', 'Offline payments waiting', $this->url('accountant.offline_payment_pending')),
                $this->kpi('Expenses this month', $this->money($expenses), 'chart', 'gold', '', $this->url('accountant.expense.list')),
            ],
            'rows' => [
                [
                    ['type' => 'bars', 'width' => 'wide', 'title' => 'Fee collection', 'subtitle' => 'Payments recorded, last six months', 'items' => $this->collectionByMonth(), 'money' => true, 'url' => $this->url('office.fees')],
                    $this->invoiceDonut($invoices, 'Fee invoices'),
                ],
                [
                    [
                        'type' => 'table', 'width' => 'wide', 'title' => 'Largest balances', 'url' => $this->url('office.fees'), 'empty' => 'Nothing is outstanding.',
                        'head' => ['Student', 'Invoice', 'Billed', 'Due'],
                        'rows' => $open->sortByDesc('due')->take(7)->map(fn ($i) => [
                            ['text' => $names[$i['student_id']]->name ?? '-', 'avatar' => $names[$i['student_id']]->name ?? '?', 'sub' => $names[$i['student_id']]->student_id ?? ''],
                            ['text' => $i['title'], 'sub' => $i['number']],
                            ['text' => $this->money($i['total']), 'num' => true],
                            ['text' => $this->money($i['due']), 'num' => true, 'strong' => true, 'tone' => 'crest'],
                        ])->values()->all(),
                    ],
                    ['type' => 'table', 'width' => 'narrow', 'compact' => true, 'title' => 'Latest receipts', 'empty' => 'No payment yet.', 'head' => ['Student', 'Amount'],
                        'rows' => array_map(fn ($r) => [$r[0], $r[4]], $this->recentPayments(6))],
                ],
            ],
        ];
    }

    private function librarian(): array
    {
        $hasCampus = Schema::hasTable('library_books');
        $books = $hasCampus ? DB::table('library_books')->where('school_id', $this->school) : DB::table('books')->where('school_id', $this->school);
        $loans = $hasCampus ? DB::table('library_loans')->where('school_id', $this->school) : null;
        $today = now()->toDateString();
        $out = $loans ? (clone $loans)->whereIn('status', ['issued', 'reserved'])->orderBy('due_on')->limit(8)->get() : collect();
        $titles = $hasCampus ? DB::table('library_books')->whereIn('id', $out->pluck('book_id'))->pluck('title', 'id') : collect();
        $names = User::whereIn('id', $out->pluck('student_id'))->pluck('name', 'id');
        $start = now(self::TZ)->startOfMonth()->subMonths(5);
        $perMonth = $loans ? (clone $loans)->where('issued_on', '>=', $start->toDateString())->selectRaw("DATE_FORMAT(issued_on, '%Y-%m') as m, count(*) as c")->groupBy('m')->pluck('c', 'm') : collect();
        $categories = $hasCampus ? (clone $books)->selectRaw("coalesce(nullif(category, ''), 'Other') as category, count(*) as c")->groupBy('category')->orderByDesc('c')->limit(4)->pluck('c', 'category') : collect();
        $tones = ['crest', 'gold', 'info', 'ok'];

        return [
            'role' => 'Librarian',
            'actions' => $this->actions(['Library desk' => ['office.library', 'book'], 'Book list' => ['librarian.book.book_list', 'layers']]),
            'kpis' => [
                $this->kpi('Titles', (clone $books)->count(), 'book', 'crest', 'In the catalogue', $this->url('office.library')),
                $this->kpi('Copies', (int) (clone $books)->sum('copies'), 'layers', 'info', 'On the shelves and out'),
                $this->kpi('On loan', $loans ? (clone $loans)->where('status', 'issued')->count() : 0, 'arrow', 'ok', 'Not yet returned', $this->url('office.library')),
                $this->kpi('Overdue', $loans ? (clone $loans)->where('status', 'issued')->where('due_on', '<', $today)->count() : 0, 'alert', 'gold', 'Past the return date', $this->url('office.library')),
            ],
            'rows' => [
                [
                    ['type' => 'bars', 'width' => 'wide', 'title' => 'Books issued', 'subtitle' => 'Last six months', 'items' => collect(range(0, 5))->map(fn ($i) => ['label' => $start->copy()->addMonths($i)->format('M'), 'value' => (int) ($perMonth[$start->copy()->addMonths($i)->format('Y-m')] ?? 0), 'note' => $start->copy()->addMonths($i)->format('F Y')])->all()],
                    ['type' => 'donut', 'width' => 'narrow', 'title' => 'Catalogue', 'subtitle' => 'Largest categories', 'center' => $categories->sum(), 'centerLabel' => 'titles', 'empty' => 'No books in the catalogue.',
                        'segments' => $categories->values()->map(fn ($c, $i) => ['label' => $categories->keys()[$i], 'value' => (int) $c, 'tone' => $tones[$i], 'note' => ''])->all()],
                ],
                [
                    [
                        'type' => 'table', 'width' => 'wide', 'title' => 'Books out', 'url' => $this->url('office.library'), 'empty' => 'No book is out.',
                        'head' => ['Book', 'Student', 'Issued', 'Due back'],
                        'rows' => $out->map(fn ($l) => [
                            ['text' => $titles[$l->book_id] ?? '-', 'strong' => true],
                            ['text' => $names[$l->student_id] ?? '-', 'avatar' => $names[$l->student_id] ?? '?'],
                            ['text' => $l->issued_on ? Carbon::parse($l->issued_on)->format('j M Y') : '-'],
                            $l->status === 'reserved' ? ['badge' => 'Reserved', 'tone' => 'info'] : ['badge' => Carbon::parse($l->due_on)->format('j M'), 'tone' => $l->due_on < $today ? 'crest' : 'ok'],
                        ])->all(),
                    ],
                    $this->notices(),
                ],
            ],
        ];
    }

    /** Attendance, balance and latest published result of one student. */
    private function standing(User $student): array
    {
        $ctx = StudentContext::for($student);

        return [
            'ctx' => $ctx,
            'attendance' => $this->attendance->forStudent($ctx)['session']['rate'] ?? null,
            'fees' => $this->finance->ledger($student)['summary'],
            'result' => $this->results->publishedFor($ctx)[0] ?? null,
        ];
    }

    private function student(): array
    {
        $s = $this->standing($this->user);
        $ctx = $s['ctx'];
        $week = $ctx->enrolled() ? $this->timetables->week(['class_id' => $ctx->classId, 'section_id' => $ctx->sectionId, 'school_id' => $ctx->schoolId, 'session_id' => $ctx->sessionId]) : [];
        $periods = collect($week)->firstWhere('day', TimetableService::todayKey())['periods'] ?? [];
        $exams = collect($this->results->upcomingExams($ctx, 5));
        $fees = $s['fees'];

        return [
            'role' => 'Student',
            'actions' => $this->actions(['Class routine' => ['student.routine', 'calendar'], 'Fees' => ['student.fee_manager.list', 'wallet']]),
            'kpis' => [
                $this->kpi('Attendance', $s['attendance'] === null ? '-' : $s['attendance'] . '%', 'check', 'ok', 'This session', $this->url('student.daily_attendance')),
                $this->kpi('Fees due', $this->money($fees['due']), 'wallet', $fees['due'] ? 'crest' : 'ok', trans_choice('{0} Nothing unpaid|{1} :count unpaid invoice|[2,*] :count unpaid invoices', $fees['open_count'], ['count' => $fees['open_count']]), $this->url('student.fee_manager.list')),
                $this->kpi('Latest GPA', isset($s['result']['gpa']) ? number_format($s['result']['gpa'], 2) : '-', 'award', 'gold', $s['result']['exam'] ?? 'No published result', $this->url('student.marks')),
                $this->kpi('Class', trim(($ctx->className ?? '-') . ' ' . ($ctx->sectionName ?? '')), 'school', 'info', $this->user->student_id ? 'ID ' . $this->user->student_id : ''),
            ],
            'rows' => [
                [
                    ['type' => 'area', 'width' => 'wide', 'title' => 'My attendance', 'subtitle' => 'Share of school days present, each month', 'suffix' => '%', 'max' => 100, 'empty' => 'No attendance recorded yet.', 'items' => $this->attendanceByMonth($this->user->id)],
                    ['type' => 'donut', 'width' => 'narrow', 'title' => 'Fees', 'subtitle' => 'Billed this session', 'center' => $this->money($fees['billed']), 'centerLabel' => 'billed', 'empty' => 'No invoice yet.',
                        'segments' => array_values(array_filter([
                            ['label' => 'Paid', 'tone' => 'ok', 'value' => $fees['paid'], 'note' => $this->money($fees['paid'])],
                            ['label' => 'Due', 'tone' => 'crest', 'value' => $fees['due'], 'note' => $this->money($fees['due'])],
                        ], fn ($x) => $x['value'] > 0))],
                ],
                [
                    [
                        'type' => 'table', 'width' => 'wide', 'title' => "Today's classes", 'url' => $this->url('student.routine'), 'empty' => 'No classes are scheduled today.',
                        'head' => ['Time', 'Subject', 'Teacher', 'Room'],
                        'rows' => collect($periods)->map(fn ($p) => [
                            ['text' => Carbon::createFromFormat('H:i', $p['start'])->format('g:i A'), 'strong' => true],
                            ['text' => $p['subject'] ?? '-'],
                            ['text' => $p['teacher'] ?? '-', 'avatar' => $p['teacher'] ?? '?'],
                            ['text' => $p['room'] ?? '-'],
                        ])->all(),
                    ],
                    [
                        'type' => 'meters', 'width' => 'narrow', 'title' => 'Latest result', 'subtitle' => $s['result']['exam'] ?? null, 'url' => $this->url('student.marks'), 'empty' => 'No result has been published yet.',
                        'items' => collect($s['result']['subjects'] ?? [])->map(fn ($x) => ['label' => $x['subject'], 'percent' => (int) round($x['mark']), 'value' => $x['grade'], 'tone' => $x['point'] >= 4 ? 'ok' : ($x['point'] > 0 ? 'gold' : 'crest')])->all(),
                    ],
                ],
                [
                    [
                        'type' => 'table', 'width' => 'wide', 'title' => 'Coming exams', 'empty' => 'No exam is scheduled.', 'head' => ['Date', 'Subject', 'Room', 'Marks'],
                        'rows' => $exams->map(fn ($e) => [
                            ['badge' => Carbon::parse($e['starts_at'])->timezone(self::TZ)->format('D, j M'), 'tone' => 'info'],
                            ['text' => $e['subject'] ?? $e['name'], 'strong' => true, 'sub' => $e['name']],
                            ['text' => $e['room'] ?? '-'],
                            ['text' => (string) $e['total_marks'], 'num' => true],
                        ])->all(),
                    ],
                    $this->notices(),
                ],
            ],
        ];
    }

    private function guardian(): array
    {
        $children = StudentContext::childrenOf($this->user)->map(fn (User $c) => ['user' => $c] + $this->standing($c));
        $due = $children->sum(fn ($c) => $c['fees']['due']);
        $rates = $children->pluck('attendance')->filter(fn ($r) => $r !== null);
        $ids = $children->pluck('user.id')->all();

        return [
            'role' => 'Guardian',
            'actions' => $this->actions(['Fees' => ['parent.fee_manager.list', 'wallet'], 'Teachers' => ['parent.teacherlist', 'users']]),
            'kpis' => [
                $this->kpi('Children', $children->count(), 'users', 'crest', 'Linked to your account', $this->url('parent.childlist')),
                $this->kpi('Average attendance', $rates->isEmpty() ? '-' : round($rates->avg()) . '%', 'check', 'ok', 'This session'),
                $this->kpi('Total fees due', $this->money($due), 'wallet', $due ? 'gold' : 'ok', 'All children', $this->url('parent.fee_manager.list')),
                $this->kpi('Unread messages', DB::table('chats')->where('reciver_id', $this->user->id)->where('read_status', 0)->count(), 'chat', 'info', '', $this->url('parent.message.chat_empty')),
            ],
            'rows' => [
                [[
                    'type' => 'children', 'width' => 'full', 'title' => 'Your children', 'url' => $this->url('parent.childlist'), 'empty' => 'No child is linked to this account yet.',
                    'items' => $children->map(fn ($c) => [
                        'name' => $c['user']->name,
                        'class' => trim(($c['ctx']->className ?? '-') . ' ' . ($c['ctx']->sectionName ?? '')),
                        'code' => $c['user']->student_id,
                        'attendance' => $c['attendance'],
                        'gpa' => isset($c['result']['gpa']) ? number_format($c['result']['gpa'], 2) : null,
                        'exam' => $c['result']['exam'] ?? null,
                        'due' => $c['fees']['due'],
                        'dueText' => $this->money($c['fees']['due']),
                    ])->all(),
                ]],
                [
                    ['type' => 'table', 'width' => 'wide', 'title' => 'Recent payments', 'url' => $this->url('parent.fee_manager.list'), 'empty' => 'No payment has been recorded yet.', 'head' => ['Child', 'For', 'Method', 'Received', 'Amount'], 'rows' => $this->recentPayments(6, $ids)],
                    $this->notices(),
                ],
            ],
        ];
    }
}
