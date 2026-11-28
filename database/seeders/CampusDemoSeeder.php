<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Campus\Models\Assignment;
use App\Modules\Campus\Models\Lesson;
use App\Modules\Campus\Models\ResultPublication;
use App\Modules\Campus\Models\ServiceTicket;
use App\Modules\Campus\Models\Submission;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\LearningService;
use App\Modules\Campus\Services\Notifier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A complete sample school for trying the apps and the office portal on a development machine:
 * php artisan db:seed --class=CampusDemoSeeder
 * Every account uses the password in PASSWORD. It refuses to run in production.
 */
class CampusDemoSeeder extends Seeder
{
    public const PASSWORD = 'demo-pass-123';
    public const SCHOOL = 1;

    private const STUDENTS = [
        'Nusrat Jahan', 'Tahmid Rahman', 'Sadia Islam', 'Arif Hossain', 'Mehjabin Chowdhury', 'Rafsan Ahmed',
        'Ishrat Karim', 'Fahim Uddin', 'Anika Tabassum', 'Sakib Mahmud', 'Lamia Akter', 'Zubair Khan',
    ];

    private const SUBJECTS = [
        'Physics' => ['Farhana Sultana', 'Vectors', ['Adding vectors', 'Resolving into components', 'Dot and cross product']],
        'Chemistry' => ['Kamrul Hasan', 'Atomic structure', ['The Bohr model', 'Quantum numbers', 'Electron configuration']],
        'Higher Math' => ['Rezaul Karim', 'Matrices', ['Types of matrices', 'Multiplication', 'Determinants']],
        'Biology' => ['Sharmin Akter', 'The cell', ['Cell membrane', 'Mitochondria', 'Cell division']],
        'English' => ['Nadia Rahman', 'Writing', ['Paragraph structure', 'Formal letters', 'Summaries']],
        'Bangla' => ['Mahbub Alam', 'গদ্য', ['অপরিচিতা', 'বিলাসী', 'আমার পথ']],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('The demo school cannot be seeded in production.');

            return;
        }

        mt_srand(2026);
        $now = CarbonImmutable::now('Asia/Dhaka');
        $stamp = ['created_at' => now(), 'updated_at' => now()];
        $session = (int) (DB::table('schools')->where('id', self::SCHOOL)->value('running_session') ?: 1);

        $admin = $this->user('office@demo.mcc.test', 'Office Administrator', 2);
        $this->user('accounts@demo.mcc.test', 'Accounts Officer', 4);
        DB::table('subscriptions')->updateOrInsert(['school_id' => self::SCHOOL, 'payment_method' => 'demo'], [
            'package_id' => 1, 'paid_amount' => 0, 'transaction_keys' => '[]', 'expire_date' => $now->addYear()->timestamp,
            'date_added' => $now->timestamp, 'active' => 1, 'status' => 1,
        ] + $stamp);

        $classId = DB::table('classes')->insertGetId(['name' => 'Class XI', 'school_id' => self::SCHOOL] + $stamp);
        $sectionId = DB::table('sections')->insertGetId(['name' => 'Science A', 'class_id' => $classId] + $stamp);

        $subjects = [];
        $teachers = [];
        foreach (self::SUBJECTS as $subject => [$teacherName, $chapter, $topics]) {
            $slug = strtolower(explode(' ', $teacherName)[0]);
            $teacher = $this->user("{$slug}@demo.mcc.test", $teacherName, 3, ['designation' => 'Lecturer', 'teacher_id' => 'T-' . strtoupper($slug)]);
            $subjectId = DB::table('subjects')->insertGetId(['name' => $subject, 'class_id' => $classId, 'school_id' => self::SCHOOL, 'session_id' => $session] + $stamp);
            $subjects[$subject] = $subjectId;
            $teachers[$subject] = $teacher;
            DB::table('teacher_permissions')->insert(['teacher_id' => $teacher->id, 'class_id' => $classId, 'section_id' => $sectionId, 'school_id' => self::SCHOOL, 'marks' => 1, 'attendance' => 1, 'updated_at' => time()]);
        }

        // Saturday to Thursday, five periods a day.
        $rooms = collect(['Room 201', 'Room 202', 'Science Lab', 'Room 305'])->map(fn ($r) => DB::table('class_rooms')->insertGetId(['name' => $r, 'school_id' => self::SCHOOL] + $stamp));
        $slots = [[9, 0, 9, 45], [9, 50, 10, 35], [10, 45, 11, 30], [11, 35, 12, 20], [13, 0, 13, 45]];
        $names = array_keys($subjects);
        // Friday is included so the sample school has classes on whichever day it is tried.
        foreach (['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $d => $day) {
            foreach ($slots as $i => [$sh, $sm, $eh, $em]) {
                $subject = $names[($d + $i) % count($names)];
                DB::table('routines')->insert([
                    'class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjects[$subject],
                    'starting_hour' => $sh, 'starting_minute' => $sm, 'ending_hour' => $eh, 'ending_minute' => $em,
                    'day' => $day, 'teacher_id' => $teachers[$subject]->id, 'room_id' => $rooms[($d + $i) % $rooms->count()],
                    'session_id' => $session, 'school_id' => self::SCHOOL,
                ] + $stamp);
            }
        }

        $guardian = $this->user('guardian@demo.mcc.test', 'Mahmuda Begum', 6, ['phone_number' => '01700000900']);
        $students = collect(self::STUDENTS)->map(function ($name, $i) use ($classId, $sectionId, $session, $stamp, $guardian) {
            $first = strtolower(explode(' ', $name)[0]);
            $student = $this->user("{$first}@demo.mcc.test", $name, 7, [
                'student_id' => 'MCC26C' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                // The first two students are sisters and brothers sharing one guardian.
                'parent_id' => $i < 2 ? $guardian->id : null,
            ]);
            DB::table('enrollments')->insert(['user_id' => $student->id, 'class_id' => $classId, 'section_id' => $sectionId, 'school_id' => self::SCHOOL, 'department_id' => 0, 'session_id' => $session] + $stamp);

            return $student;
        });

        // Eight weeks of attendance up to yesterday, plus today for all but the demo student's class teacher to mark.
        for ($back = 56; $back >= 0; $back--) {
            $day = $now->subDays($back)->startOfDay();
            if ($day->isFriday()) {
                continue;
            }
            foreach ($students as $i => $student) {
                $roll = mt_rand(1, 100);
                $status = $roll > 93 - ($i % 4) * 3 ? 0 : ($roll > 84 - ($i % 4) * 3 ? 2 : 1);
                if ($back === 0 && $i > 1) {
                    continue;
                }
                DB::table('daily_attendances')->insert([
                    'class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $student->id,
                    'status' => $back === 0 ? 1 : $status, 'note' => $status === 0 && $back ? 'Family reason' : null,
                    'session_id' => $session, 'school_id' => self::SCHOOL, 'timestamp' => $day->timestamp,
                ] + $stamp);
            }
        }

        foreach (self::SUBJECTS as $subject => [$teacherName, $chapter, $topics]) {
            foreach ($topics as $i => $topic) {
                $lesson = Lesson::create([
                    'school_id' => self::SCHOOL, 'class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjects[$subject],
                    'teacher_id' => $teachers[$subject]->id, 'chapter' => $chapter, 'title' => $topic,
                    'summary' => "Key ideas of " . $topic . ", with one worked example to try before class.",
                    'content' => "Read the matching section of your textbook.\n\nThen answer in your notebook:\n1. What is the main idea?\n2. Give one example from daily life.\n3. Write one question to ask in class.",
                    'status' => 'published', 'published_at' => now()->subDays(20 - $i * 5),
                ]);
                if ($i === 0) {
                    DB::table('lms_lesson_progress')->insert(['lesson_id' => $lesson->id, 'student_id' => $students[0]->id, 'completed_at' => now()->subDays(10)]);
                }
                DB::table('teacher_syllabus_topics')->insert([
                    'teacher_id' => $teachers[$subject]->id, 'class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjects[$subject],
                    'topic' => $topic, 'sort_order' => $i, 'completed_at' => $i < 2 ? now()->subDays(8) : null, 'school_id' => self::SCHOOL,
                ] + $stamp);
            }
        }

        $learning = app(LearningService::class);
        $work = [
            ['Physics', 'Vector problem set', 'Solve questions 1 to 8 from the end of the chapter. Show every step.', 20, 2, null],
            ['English', 'Formal letter to the principal', 'Ask for a study tour. 150 to 200 words.', 10, 5, null],
            ['Higher Math', 'Matrix multiplication drill', 'Complete the worksheet handed out in class.', 15, -1, null],
            ['Chemistry', 'Electron configuration table', 'First thirty elements, in your own handwriting.', 10, -9, 8.5],
        ];
        foreach ($work as [$subject, $title, $instructions, $marks, $days, $score]) {
            $due = now()->addDays($days)->setTime(17, 0);
            $assignment = Assignment::create([
                'teacher_id' => $teachers[$subject]->id, 'school_id' => self::SCHOOL, 'class_id' => $classId, 'section_id' => $sectionId,
                'subject_id' => $subjects[$subject], 'title' => $title, 'description' => $instructions, 'total_marks' => $marks,
                'due_at' => $due, 'due_date' => $due->toDateString(), 'allow_late' => true, 'total_students' => $students->count(),
            ]);
            if ($score !== null) {
                foreach ($students->take(5) as $i => $student) {
                    Submission::create([
                        'assignment_id' => $assignment->id, 'student_id' => $student->id, 'body' => 'My completed table is attached in class.',
                        'submitted_at' => $due->copy()->subDay(), 'marks' => $i === 0 ? $score : mt_rand(5, 10),
                        'feedback' => $i === 0 ? 'Neat work. Check the order of 4s and 3d.' : null, 'graded_by' => $teachers[$subject]->id, 'graded_at' => $due,
                    ]);
                }
            } elseif ($subject === 'Physics') {
                foreach ($students->slice(2, 4) as $student) {
                    Submission::create(['assignment_id' => $assignment->id, 'student_id' => $student->id, 'body' => 'Answers for questions 1 to 8.', 'submitted_at' => now()->subHours(5)]);
                }
            }
            $learning->refreshCounts($assignment);
        }

        foreach ([['First term examination', 'published'], ['Monthly test, October', 'submitted']] as $n => [$exam, $state]) {
            $category = DB::table('exam_categories')->insertGetId(['name' => $exam, 'school_id' => self::SCHOOL, 'session_id' => $session, 'timestamp' => time()] + $stamp);
            foreach ($students as $i => $student) {
                $marks = [];
                foreach ($subjects as $subjectId) {
                    $marks[$subjectId] = max(28, min(98, 92 - ($i * 4) + mt_rand(-9, 9) + $n * 2));
                }
                DB::table('gradebooks')->insert([
                    'class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $student->id, 'exam_category_id' => $category,
                    'marks' => json_encode($marks), 'comment' => $i === 0 ? 'Strong and steady. Practise longer written answers.' : '',
                    'school_id' => self::SCHOOL, 'session_id' => $session, 'timestamp' => time(),
                ] + $stamp);
            }
            ResultPublication::create([
                'school_id' => self::SCHOOL, 'session_id' => $session, 'exam_category_id' => $category, 'class_id' => $classId, 'section_id' => $sectionId,
                'status' => $state, 'submitted_by' => $teachers['Physics']->id, 'submitted_at' => now()->subDays(3),
                'published_by' => $state === 'published' ? $admin->id : null, 'published_at' => $state === 'published' ? now()->subDays(12) : null,
            ]);
        }

        $preBoard = DB::table('exam_categories')->insertGetId(['name' => 'Pre-board examination', 'school_id' => self::SCHOOL, 'session_id' => $session, 'timestamp' => time()] + $stamp);
        foreach ([['Pre-board examination', 9, 'Physics'], ['Pre-board examination', 11, 'Chemistry'], ['Pre-board examination', 13, 'Higher Math']] as [$name, $in, $subject]) {
            $start = $now->addDays($in)->setTime(10, 0);
            DB::table('exams')->insert([
                'name' => $name, 'exam_category_id' => $preBoard, 'exam_type' => 'offline', 'starting_time' => (string) $start->timestamp,
                'ending_time' => (string) $start->addHours(3)->timestamp, 'total_marks' => 100, 'status' => 'pending', 'class_id' => $classId,
                'subject_id' => $subjects[$subject], 'school_id' => self::SCHOOL, 'session_id' => $session, 'room_number' => 'Hall 1',
            ] + $stamp);
        }

        $finance = app(FinanceService::class);
        $month = $now->startOfMonth();
        $finance->billClass(self::SCHOOL, $session, $classId, $sectionId, 'Tuition, ' . $month->subMonths(2)->format('F Y'), 2500);
        $finance->billClass(self::SCHOOL, $session, $classId, $sectionId, 'Tuition, ' . $month->subMonth()->format('F Y'), 2500);
        $finance->billClass(self::SCHOOL, $session, $classId, $sectionId, 'Tuition, ' . $month->format('F Y'), 2500, 250);
        $finance->billClass(self::SCHOOL, $session, $classId, $sectionId, 'Laboratory fee', 800);
        $position = $students->pluck('id')->flip();
        foreach (DB::table('student_fee_managers')->whereIn('student_id', $students->pluck('id'))->orderBy('id')->get() as $invoice) {
            // Two months ago is settled for all; last month for two students in three.
            $old = str_contains($invoice->title, $month->subMonths(2)->format('F')) || (str_contains($invoice->title, $month->subMonth()->format('F')) && $position[$invoice->student_id] % 3 !== 2);
            if ($old) {
                $finance->record($invoice->id, (int) $invoice->total_amount, $invoice->id % 2 ? 'cash' : 'bkash', null, $admin->id);
            } elseif ($invoice->title === 'Laboratory fee' && $invoice->student_id === $students[0]->id) {
                $finance->record($invoice->id, 300, 'cash', null, $admin->id);
            }
        }

        foreach ([
            ['Pre-board routine published', 'The pre-board examination starts next week. Collect admit cards from the office.', 0],
            ['Parent meeting on Saturday', 'Guardians of Class XI are invited at 10:00 AM in the auditorium.', 3],
            ['Library hours extended', 'The library now stays open until 5:00 PM on class days.', 9],
        ] as [$title, $body, $ago]) {
            DB::table('noticeboard')->insert([
                'notice_title' => $title, 'notice' => $body, 'start_date' => (string) $now->subDays($ago)->timestamp, 'start_time' => '', 'end_date' => '',
                'end_time' => '', 'status' => 1, 'show_on_website' => 0, 'image' => '', 'school_id' => self::SCHOOL, 'session_id' => $session,
            ] + $stamp);
        }

        $ticket = ServiceTicket::create(['school_id' => self::SCHOOL, 'requester_id' => $guardian->id, 'student_id' => $students[0]->id, 'category' => 'id_card', 'subject' => 'Replacement ID card', 'status' => 'in_progress']);
        $ticket->messages()->create(['user_id' => $guardian->id, 'body' => 'The ID card was lost on the way home. How can we get a new one?']);
        $ticket->messages()->create(['user_id' => $admin->id, 'body' => 'We are printing it now. It will be ready on Sunday at the front desk.', 'from_staff' => true]);

        app(Notifier::class)->toFamilies([$students[0]->id], 'result', 'Result published', 'First term examination results are ready.', ['screen' => 'results']);

        $this->call(CampusModulesDemoSeeder::class);

        $this->command?->info('Demo school ready. Password for every account: ' . self::PASSWORD);
        $this->command?->table(['Role', 'Sign in with'], [
            ['Student', $students[0]->student_id], ['Guardian', $guardian->phone_number], ['Teacher', 'farhana@demo.mcc.test'],
            ['Office (web)', $admin->email], ['Accounts (web)', 'accounts@demo.mcc.test'],
        ]);
    }

    private function user(string $email, string $name, int $role, array $extra = []): User
    {
        return User::updateOrCreate(['email' => $email], array_merge([
            'name' => $name,
            'role_id' => $role,
            'school_id' => self::SCHOOL,
            'status' => 1,
            'user_type' => 'regular',
            'account_status' => 'enable',
            'password' => Hash::make(self::PASSWORD),
            'user_information' => json_encode(['gender' => '', 'blood_group' => '', 'birthday' => '', 'phone' => $extra['phone_number'] ?? '', 'address' => 'Savar, Dhaka', 'photo' => '']),
        ], $extra));
    }
}
