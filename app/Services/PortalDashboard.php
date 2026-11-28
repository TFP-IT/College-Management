<?php

namespace App\Services;

use App\Models\User;
use App\Modules\Campus\Services\AttendanceService;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Services\TimetableService;
use App\Modules\Campus\Support\StudentContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The figures on each role's home page of the school portal. Everything is read from the signed-in
 * person's own school, and a figure with nothing behind it is shown as a dash rather than invented.
 *
 * Shape: stats[{label, value, note, url, tone}], panels[{type, title, ...}], aside[...], actions[{label, url}].
 */
class PortalDashboard
{
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

        return $page + ['stats' => [], 'panels' => [], 'actions' => [], 'aside' => $this->school ? $this->aside() : []];
    }

    // ---- Shared -------------------------------------------------------------

    /** A link only when the page exists, so a missing route never breaks the dashboard. */
    private function url(string $route, array $parameters = []): ?string
    {
        return Route::has($route) ? route($route, $parameters) : null;
    }

    private function stat(string $label, mixed $value, string $note = '', ?string $url = null, string $tone = ''): array
    {
        return compact('label', 'value', 'note', 'url', 'tone');
    }

    private function actions(array $items): array
    {
        return collect($items)->map(fn ($route, $label) => ['label' => $label, 'url' => $this->url($route)])->filter(fn ($a) => $a['url'])->values()->all();
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

    private function aside(): array
    {
        $prefix = $this->prefix();

        return [
            [
                'title' => 'Upcoming events',
                'url' => $this->url("{$prefix}.events.list"),
                'empty' => 'No events are scheduled.',
                'items' => DB::table('frontend_events')->where('school_id', $this->school)->where('status', 1)
                    ->where('timestamp', '>=', now()->startOfDay()->timestamp)->orderBy('timestamp')->limit(4)->get()
                    ->map(fn ($e) => ['title' => $e->title, 'note' => date('l, j M Y', (int) $e->timestamp), 'day' => date('j', (int) $e->timestamp), 'month' => date('M', (int) $e->timestamp)])->all(),
            ],
            [
                'title' => 'Noticeboard',
                'url' => $this->url("{$prefix}.noticeboard.list"),
                'empty' => 'No notices yet.',
                'items' => DB::table('noticeboard')->where('school_id', $this->school)->orderByDesc('id')->limit(4)->get()
                    ->map(function ($n) {
                        $starts = $this->date($n->start_date);

                        return [
                            'title' => $n->notice_title,
                            'note' => Str::limit(trim(strip_tags((string) $n->notice)), 80),
                            'day' => $starts ? date('j', $starts) : null,
                            'month' => $starts ? date('M', $starts) : null,
                        ];
                    })->all(),
            ],
        ];
    }

    /** Older screens store dates as Unix stamps, newer ones as text. */
    private function date(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : (strtotime((string) $value) ?: null);
    }

    /** Invoices of this school with what is really still owed on each. */
    private function invoices()
    {
        return $this->finance->invoices(\App\Models\StudentFeeManager::where('school_id', $this->school)->get());
    }

    private function collectedThisMonth(): int
    {
        // Same rule as the accounts report: invoices settled this month.
        return (int) DB::table('student_fee_managers')->where('school_id', $this->school)->where('status', 'paid')
            ->whereBetween('timestamp', [now()->startOfMonth()->timestamp, now()->endOfMonth()->timestamp])->sum('total_amount');
    }

    private function nameList($ids)
    {
        return User::whereIn('id', $ids)->pluck('name', 'id');
    }

    // ---- Roles --------------------------------------------------------------

    private function superadmin(): array
    {
        $year = now()->year;
        $payments = DB::table('subscriptions')->whereBetween('date_added', [now()->startOfYear()->timestamp, now()->endOfYear()->timestamp])->get(['paid_amount', 'date_added']);
        $months = collect(range(1, 12))->map(fn ($m) => [
            'label' => date('M', mktime(0, 0, 0, $m, 1)),
            'value' => (float) $payments->filter(fn ($p) => (int) date('n', (int) $p->date_added) === $m)->sum('paid_amount'),
        ]);

        return [
            'stats' => [
                $this->stat('Schools', DB::table('schools')->count(), 'Registered on this system', $this->url('superadmin.school.list')),
                $this->stat('Active subscriptions', DB::table('subscriptions')->where('expire_date', '>=', time())->count(), 'Not yet expired', $this->url('superadmin.subscription.report')),
                $this->stat('Received this year', number_format($months->sum('value')), 'Subscription payments, ' . $year, $this->url('superadmin.subscription.report')),
                $this->stat('Waiting for review', DB::table('payment_history')->where('paid_by', 'offline')->where('status', 'pending')->count(), 'Offline payment requests', $this->url('superadmin.subscription.pending')),
            ],
            'panels' => [['type' => 'columns', 'title' => "Subscription payments, {$year}", 'items' => $months->all()]],
            'actions' => $this->actions(['Add a school' => 'superadmin.school.add', 'Packages' => 'superadmin.package']),
            'aside' => [],
        ];
    }

    private function admin(): array
    {
        $enrolled = DB::table('enrollments')->where('school_id', $this->school)->when($this->session, fn ($q) => $q->where('session_id', $this->session));
        $byClass = (clone $enrolled)->selectRaw('class_id, count(*) as c')->groupBy('class_id')->pluck('c', 'class_id');
        $start = now()->startOfDay()->timestamp;
        $today = DB::table('daily_attendances')->where('school_id', $this->school)->whereBetween('timestamp', [$start, $start + 86399])
            ->get(['class_id', 'student_id', 'status'])->unique('student_id');
        $names = DB::table('classes')->where('school_id', $this->school)->orderBy('id')->pluck('name', 'id');
        $present = $today->whereIn('status', [1, 2])->count();
        $invoices = $this->invoices();
        $collected = $this->collectedThisMonth();
        $due = $invoices->sum('due');

        return [
            'stats' => [
                $this->stat('Students', $this->people(7), (clone $enrolled)->count() . ' enrolled this session', $this->url('admin.student')),
                $this->stat('Staff', $this->people(2) + $this->people(3) + $this->people(4) + $this->people(5), $this->people(3) . ' teachers', $this->url('admin.teacher')),
                $this->stat('Present today', $today->isEmpty() ? '-' : $present, $today->isEmpty() ? 'Attendance not taken yet' : "of {$today->count()} marked", $this->url('admin.daily_attendance')),
                $this->stat('Fees outstanding', $this->money($due), $invoices->where('due', '>', 0)->pluck('student_id')->unique()->count() . ' students with a balance', $this->url('admin.fee_manager.list'), $due ? 'due' : ''),
            ],
            'panels' => [
                [
                    'type' => 'bars', 'title' => "Today's attendance by class", 'url' => $this->url('admin.daily_attendance'), 'empty' => 'No classes are set up.',
                    'items' => $names->map(function ($name, $id) use ($today, $byClass) {
                        $marked = $today->where('class_id', $id);
                        $in = $marked->whereIn('status', [1, 2])->count();
                        $total = (int) ($byClass[$id] ?? 0);

                        return ['label' => $name, 'percent' => $total ? round($in / $total * 100) : 0, 'value' => $marked->isEmpty() ? 'Not marked' : "{$in} of {$total}"];
                    })->values()->all(),
                ],
                [
                    'type' => 'figures', 'title' => 'Fees', 'url' => $this->url('admin.fee_manager.list'),
                    'items' => [
                        ['label' => 'Collected this month', 'value' => $this->money($collected), 'tone' => 'ok'],
                        ['label' => 'Billed in total', 'value' => $this->money($invoices->sum('total'))],
                        ['label' => 'Still to collect', 'value' => $this->money($due), 'tone' => $due ? 'due' : 'ok'],
                    ],
                ],
            ],
            'actions' => $this->actions(['Office portal' => 'office.dashboard', 'Take attendance' => 'admin.daily_attendance', 'Students' => 'admin.student']),
        ];
    }

    private function teacher(): array
    {
        $classes = $this->classroom->classes($this->user);
        $week = $this->timetables->week(['teacher_id' => $this->user->id, 'school_id' => $this->school, 'session_id' => $this->session]);
        $periods = collect($week)->firstWhere('day', TimetableService::todayKey())['periods'] ?? [];
        $sections = $classes->unique(fn ($c) => "{$c['class_id']}-{$c['section_id']}");
        $students = $sections->sum(fn ($c) => DB::table('enrollments')->where('class_id', $c['class_id'])->where('section_id', $c['section_id'])
            ->when($this->session, fn ($q) => $q->where('session_id', $this->session))->count());

        return [
            'stats' => [
                $this->stat('Periods today', count($periods), count($periods) ? 'On your routine' : 'No class today', $this->url('teacher.routine')),
                $this->stat('My sections', $sections->count(), trans_choice('{1} :count subject|[0,*] :count subjects', $n = $classes->pluck('subject_id')->filter()->unique()->count(), ['count' => $n])),
                $this->stat('My students', $students, 'In those sections'),
                $this->stat('Unread messages', DB::table('chats')->where('reciver_id', $this->user->id)->where('read_status', 0)->count(), '', $this->url('teacher.message.chat_empty')),
            ],
            'actions' => $this->actions(['Take attendance' => 'teacher.daily_attendance', 'Enter marks' => 'teacher.marks', 'Class routine' => 'teacher.routine']),
        ];
    }

    private function accountant(): array
    {
        $invoices = $this->invoices();
        $open = $invoices->where('due', '>', 0);
        $waiting = $invoices->where('status', 'verifying');
        $expenses = (int) DB::table('expenses')->where('school_id', $this->school)->whereBetween('date', [now()->startOfMonth()->timestamp, now()->endOfMonth()->timestamp])->sum('amount');
        $names = $this->nameList($open->pluck('student_id'));
        $largest = $open->sortByDesc('due')->take(8);

        return [
            'stats' => [
                $this->stat('Collected this month', $this->money($this->collectedThisMonth()), now()->format('F Y'), $this->url('accountant.fee_manager.list'), 'ok'),
                $this->stat('Outstanding', $this->money($open->sum('due')), $open->count() . ' open invoices', $this->url('accountant.fee_manager.list'), $open->count() ? 'due' : ''),
                $this->stat('To verify', $waiting->count(), 'Offline payments waiting', $this->url('accountant.offline_payment_pending')),
                $this->stat('Expenses this month', $this->money($expenses), '', $this->url('accountant.expense.list')),
            ],
            'panels' => [[
                'type' => 'table', 'title' => 'Largest balances', 'url' => $this->url('accountant.fee_manager.list'), 'empty' => 'Nothing is outstanding.',
                'head' => ['Student', 'Invoice', 'Due'],
                'rows' => $largest->map(fn ($i) => [$names[$i['student_id']] ?? '-', $i['title'], $this->money($i['due'])])->values()->all(),
            ]],
            'actions' => $this->actions(['Fee manager' => 'accountant.fee_manager.list', 'Office portal' => 'office.dashboard']),
        ];
    }

    private function librarian(): array
    {
        $books = DB::table('books')->where('school_id', $this->school);
        $issued = DB::table('book_issues')->where('school_id', $this->school)->where('status', 0)->orderByDesc('issue_date')->get();
        $titles = DB::table('books')->whereIn('id', $issued->pluck('book_id'))->pluck('name', 'id');
        $names = $this->nameList($issued->pluck('student_id'));

        return [
            'stats' => [
                $this->stat('Titles', (clone $books)->count(), 'In the catalogue', $this->url('librarian.book.book_list')),
                $this->stat('Copies', (int) (clone $books)->sum('copies'), '', $this->url('librarian.book.book_list')),
                $this->stat('Issued now', $issued->count(), 'Not yet returned', $this->url('librarian.book_issue.list')),
                $this->stat('Students', $this->people(7), 'Who can borrow'),
            ],
            'panels' => [[
                'type' => 'table', 'title' => 'Books out', 'url' => $this->url('librarian.book_issue.list'), 'empty' => 'No book is out from this list.',
                'head' => ['Book', 'Student', 'Issued'],
                'rows' => $issued->take(8)->map(fn ($i) => [$titles[$i->book_id] ?? '-', $names[$i->student_id] ?? '-', date('j M Y', (int) $i->issue_date)])->all(),
            ]],
            'actions' => $this->actions(['Office library' => 'office.library', 'Issue a book' => 'librarian.book_issue.list', 'Books' => 'librarian.book.book_list']),
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
        $exams = collect($this->results->upcomingExams($ctx, 6));

        return [
            'stats' => [
                $this->stat('Attendance', $s['attendance'] === null ? '-' : $s['attendance'] . '%', 'This session', $this->url('student.daily_attendance')),
                $this->stat('Fees due', $this->money($s['fees']['due']), $s['fees']['open_count'] . ' unpaid invoices', $this->url('student.fee_manager.list'), $s['fees']['due'] ? 'due' : ''),
                $this->stat('Latest GPA', isset($s['result']['gpa']) ? number_format($s['result']['gpa'], 2) : '-', $s['result']['exam'] ?? 'No published result'),
                $this->stat('Class', trim(($ctx->className ?? '-') . ' ' . ($ctx->sectionName ?? '')), $this->user->student_id ? 'ID ' . $this->user->student_id : ''),
            ],
            'panels' => [
                [
                    'type' => 'table', 'title' => "Today's classes", 'url' => $this->url('student.routine'), 'empty' => 'No classes are scheduled today.',
                    'head' => ['Time', 'Subject', 'Teacher', 'Room'],
                    'rows' => collect($periods)->map(fn ($p) => [Carbon::createFromFormat('H:i', $p['start'])->format('g:i A'), $p['subject'] ?? '-', $p['teacher'] ?? '-', $p['room'] ?? '-'])->all(),
                ],
                [
                    'type' => 'table', 'title' => 'Coming exams', 'empty' => 'No exam is scheduled.',
                    'head' => ['Date', 'Subject', 'Room'],
                    'rows' => $exams->map(fn ($e) => [Carbon::parse($e['starts_at'])->timezone('Asia/Dhaka')->format('D, j M'), $e['subject'] ?? $e['name'], $e['room'] ?? '-'])->all(),
                ],
            ],
            'actions' => $this->actions(['Class routine' => 'student.routine', 'Marks' => 'student.marks']),
        ];
    }

    private function guardian(): array
    {
        $children = StudentContext::childrenOf($this->user)->map(fn (User $c) => ['user' => $c] + $this->standing($c));

        return [
            'stats' => [
                $this->stat('Children', $children->count(), 'Linked to your account', $this->url('parent.childlist')),
                $this->stat('Total fees due', $this->money($children->sum(fn ($c) => $c['fees']['due'])), '', $this->url('parent.fee_manager.list'), $children->sum(fn ($c) => $c['fees']['due']) ? 'due' : ''),
                $this->stat('Unread messages', DB::table('chats')->where('reciver_id', $this->user->id)->where('read_status', 0)->count(), '', $this->url('parent.message.chat_empty')),
            ],
            'panels' => [[
                'type' => 'table', 'title' => 'Your children', 'url' => $this->url('parent.childlist'), 'empty' => 'No child is linked to this account yet.',
                'head' => ['Name', 'Class', 'Attendance', 'Latest GPA', 'Fees due'],
                'rows' => $children->map(fn ($c) => [
                    $c['user']->name, trim(($c['ctx']->className ?? '-') . ' ' . ($c['ctx']->sectionName ?? '')),
                    $c['attendance'] === null ? '-' : $c['attendance'] . '%',
                    isset($c['result']['gpa']) ? number_format($c['result']['gpa'], 2) : '-', $this->money($c['fees']['due']),
                ])->all(),
            ]],
            'actions' => $this->actions(['Fees' => 'parent.fee_manager.list', 'Teachers' => 'parent.teacherlist']),
        ];
    }
}
