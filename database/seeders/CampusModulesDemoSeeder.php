<?php

namespace Database\Seeders;

use App\Console\Commands\GrantAdmissionsRole;
use App\Models\User;
use App\Modules\Campus\Engine\Module;
use App\Modules\Campus\Engine\Records;
use App\Modules\Campus\Engine\Workflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/** Sample data for every campus module. Run through CampusDemoSeeder; refuses production. */
class CampusModulesDemoSeeder extends Seeder
{
    /** email prefix => [name, role] */
    private const STAFF = [
        'principal' => ['Dr. Anwar Hossain', 'Principal'], 'hr' => ['Rumana Parvin', 'HR Officer'], 'discipline' => ['Masud Karim', 'Discipline Officer'],
        'safeguarding' => ['Salma Khatun', 'Safeguarding Officer'], 'counsellor' => ['Tanvir Ahsan', 'Counsellor'], 'registrar' => ['Habibur Rahman', 'Registrar'],
        'transport' => ['Jahangir Alam', 'Transport Coordinator'], 'sports' => ['Rakib Hasan', 'Sports Coordinator'], 'career' => ['Nasrin Sultana', 'Career Coordinator'],
        'innovation' => ['Imran Kabir', 'Innovation Coordinator'], 'operations' => ['Delwar Hossain', 'Operations Manager'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Demo data cannot be seeded in production.');

            return;
        }

        $school = CampusDemoSeeder::SCHOOL;
        $session = (int) (DB::table('schools')->where('id', $school)->value('running_session') ?: 1);
        $records = app(Records::class);
        $workflow = app(Workflow::class);

        foreach (GrantAdmissionsRole::OFFICE_ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
        $staff = collect(self::STAFF)->map(function ($s, $prefix) use ($school) {
            $user = $this->account("{$prefix}@demo.mcc.test", $s[0], 8, $school);
            $user->syncRoles([$s[1]]);

            return $user;
        });
        $librarian = $this->account('librarian@demo.mcc.test', 'Shirin Akhter', 5, $school);

        $admin = User::where('email', 'office@demo.mcc.test')->firstOrFail();
        $guardian = User::where('email', 'guardian@demo.mcc.test')->firstOrFail();
        $teacher = User::where('email', 'farhana@demo.mcc.test')->firstOrFail();
        $student = User::where('email', 'nusrat@demo.mcc.test')->firstOrFail();
        $others = User::whereIn('email', ['tahmid@demo.mcc.test', 'sadia@demo.mcc.test', 'arif@demo.mcc.test', 'lamia@demo.mcc.test'])->orderBy('id')->get();
        $ctx = DB::table('enrollments')->where('user_id', $student->id)->first();

        $make = function (string $type, array $fields, array $context = [], ?User $by = null) use ($records, $school, $admin) {
            $module = Module::find($type);
            if (isset($fields['title']) && !isset($context['title'])) {
                $context['title'] = ($module->fields()['title']['type'] ?? '') === 'select' ? ucfirst($fields['title']) : $fields['title'];
            }

            return $records->create($module, $fields, $school, $by ?? $admin, $context);
        };
        $day = fn (int $in) => now()->addDays($in)->toDateString();
        $at = fn (int $in, int $hour) => now('Asia/Dhaka')->addDays($in)->setTime($hour, 0)->toIso8601String();

        foreach ([['Victory Day holiday', 'holiday', 18], ['Pre-board examination begins', 'exam', 9], ['Annual sports day', 'event', 30], ['First term result day', 'result day', 45]] as [$t, $k, $in]) {
            $make('calendar_event', ['title' => $t, 'kind' => $k, 'starts_at' => $day($in)]);
        }

        // Finance
        $program = $make('scholarship_program', ['title' => 'Merit scholarship 2026', 'kind' => 'merit', 'percent' => 25, 'ends_at' => $day(40), 'details' => 'For students with a GPA of 4.50 or higher and good attendance.']);
        $make('scholarship_application', ['title' => $program->title, 'reason' => 'I earned GPA 5.00 in the first term and would like support with tuition.'], ['student_id' => $student->id, 'parent_id' => $program->id], $guardian);
        $make('fee_plan', ['title' => 'Tuition', 'section' => "{$ctx->class_id}-{$ctx->section_id}", 'amount' => 2500, 'due_day' => 10, 'late_fee' => 100, 'grace_days' => 5]);

        // Conduct and welfare
        $make('commendation', ['title' => 'Helped a classmate catch up', 'kind' => 'helping others', 'details' => 'Spent two lunch breaks explaining vectors.'], ['student_id' => $student->id], $teacher);
        $case = $make('discipline_case', ['title' => 'Late to class three times', 'kind' => 'late attendance', 'severity' => 'minor', 'starts_at' => $day(-4), 'details' => 'Arrived after the bell on three days this week.'], ['student_id' => $others[0]->id], $teacher);
        $records->setStatus(Module::find('discipline_case'), $case, 'review', $staff['discipline']);
        $make('counselling_request', ['title' => 'Managing exam stress', 'category' => 'study stress', 'starts_at' => $at(3, 12)], ['student_id' => $student->id], $student);
        $make('safeguarding_concern', ['title' => 'Unkind messages in a group chat', 'kind' => 'bullying', 'details' => 'A student reports repeated unkind messages from classmates.'], ['student_id' => $others[1]->id], $teacher);

        // Transport and services
        $route = $make('transport_route', ['title' => 'Route 1: Nabinagar to campus', 'stops' => "Nabinagar 7:20 AM\nBank Town 7:30 AM\nRadio Colony 7:40 AM", 'vehicle' => 'DHA-METRO-B-11-2345', 'driver' => 'Abdul Mannan', 'seats' => 40, 'amount' => 1200]);
        $make('transport_application', ['title' => $route->title, 'stop' => 'Bank Town', 'amount' => 1200], ['student_id' => $student->id, 'parent_id' => $route->id], $guardian);
        $make('uniform_order', ['title' => 'sports kit', 'size' => 'M', 'quantity' => 1, 'amount' => 900], ['student_id' => $student->id], $guardian);
        $make('facility_request', ['title' => 'Projector not turning on', 'kind' => 'electrical', 'place' => 'Room 201', 'priority' => 'high'], [], $teacher);

        // Activities, future and innovation
        $debate = $make('activity', ['title' => 'Debate Club', 'kind' => 'debate', 'coach' => 'Nadia Rahman', 'schedule' => 'Sunday and Tuesday, 2:00 PM', 'seats' => 30, 'details' => 'Practise argument, research and public speaking.']);
        $make('activity', ['title' => 'Football Team', 'kind' => 'sport', 'coach' => 'Rakib Hasan', 'schedule' => 'Monday and Thursday, 3:30 PM', 'seats' => 22]);
        $member = $make('activity_membership', ['title' => $debate->title, 'role' => 'secretary'], ['student_id' => $student->id, 'parent_id' => $debate->id], $student);
        $workflow->act($workflow->forRecord(Module::find('activity_membership'), $member->id)->id, $staff['sports'], true);
        $make('activity_event', ['title' => 'Inter-house debate final', 'starts_at' => $at(12, 14), 'place' => 'Auditorium']);
        $make('achievement', ['title' => 'Debate competition winner', 'kind' => 'culture', 'starts_at' => $day(-40), 'details' => 'First place, inter-college debate.'], ['student_id' => $student->id]);
        $make('career_profile', ['title' => 'Mechanical engineer', 'field' => 'engineering', 'universities' => "BUET\nRUET", 'skills' => 'Mathematics, problem solving', 'readiness' => 62, 'roadmap' => 'Strengthen Higher Math and Physics, sit mock admission tests from March.'], ['student_id' => $student->id], $student);
        $make('university_application', ['title' => 'BUET', 'program' => 'Mechanical Engineering', 'country' => 'Bangladesh', 'ends_at' => $day(75)], ['student_id' => $student->id], $student);
        $make('ielts_test', ['title' => 'diagnostic', 'starts_at' => $day(-20), 'listening' => 6.5, 'reading' => 6, 'writing' => 5.5, 'speaking' => 6.5, 'target' => 7, 'feedback' => 'Focus on essay structure. Two speaking sessions each week.'], ['student_id' => $student->id], $teacher);
        $make('goal', ['title' => 'Reach IELTS 7.0', 'kind' => 'language', 'current' => '6.0', 'target' => '7.0', 'ends_at' => $day(150), 'actions' => 'One mock test each month'], ['student_id' => $student->id], $student);
        $make('mentoring', ['title' => 'University preparation', 'starts_at' => $at(5, 13), 'actions' => 'Bring last term result and a list of target universities.'], ['student_id' => $student->id, 'staff_id' => $teacher->id]);
        $make('opportunity', ['title' => 'National science fair', 'kind' => 'competition', 'ends_at' => $day(25), 'details' => 'Teams of up to three may submit a working project.']);
        $track = $make('innovation_track', ['title' => 'Robotics', 'level' => 'beginner', 'mentor' => 'Imran Kabir', 'schedule' => 'Saturday 2:00 PM', 'details' => 'Sensors, motor control, first robot project.']);
        $make('innovation_project', ['title' => 'Line following robot', 'category' => 'robotics', 'objective' => 'A small robot that follows a marked path.', 'team' => 'Nusrat (lead), Sadia (design)'], ['student_id' => $student->id], $student);
        $make('competition', ['title' => 'College programming contest', 'kind' => 'programming', 'starts_at' => $day(20), 'ends_at' => $day(15), 'details' => 'Open to Class XI and XII. Individual entries.']);
        $make('appointment', ['title' => 'Discuss Physics progress', 'mode' => 'in person', 'starts_at' => $at(2, 11)], ['student_id' => $student->id, 'staff_id' => $teacher->id], $guardian);

        // Staff, operations, alumni
        $make('leave_request', ['title' => 'Family event', 'kind' => 'casual', 'starts_at' => $day(14), 'ends_at' => $day(15)], ['staff_id' => $teacher->id], $teacher);
        $make('payslip', ['period' => now()->subMonth()->startOfMonth()->toDateString(), 'basic' => 32000, 'allowances' => 8000, 'deductions' => 1500], ['staff_id' => $teacher->id, 'status' => 'issued']);
        $make('training', ['title' => 'Classroom assessment workshop', 'provider' => 'MCC', 'start_date' => $day(8), 'hours' => 6], ['staff_id' => $teacher->id]);
        $make('purchase_request', ['title' => 'Physics lab lenses', 'items' => '12 convex lenses, 12 holders', 'amount' => 8500, 'reason' => 'Replace damaged sets'], [], $teacher);
        $make('vendor', ['title' => 'Savar Stationers', 'category' => 'stationery', 'contact' => 'Bazar Road, Savar'], ['status' => 'approved']);
        $make('contract', ['title' => 'Bus service agreement', 'partner' => 'City Transport', 'starts_at' => $day(-300), 'ends_at' => $day(55), 'amount' => 480000, 'owner' => 'Transport']);
        $make('asset', ['title' => 'Projector, Room 201', 'tag' => 'MCC-PR-014', 'category' => 'projector', 'department' => 'Science', 'amount' => 42000, 'ends_at' => $day(20)]);
        $make('inventory_item', ['title' => 'Exercise books', 'opening' => 400, 'received' => 200, 'issued' => 540, 'damaged' => 5, 'reorder_at' => 100]);
        $make('compliance_item', ['title' => 'Vehicle fitness renewal', 'kind' => 'vehicle fitness', 'ends_at' => $day(35), 'owner' => 'Transport']);
        $make('policy_document', ['title' => 'Student conduct policy', 'category' => 'policy', 'version' => '2.0', 'body' => 'Respect, punctuality and honesty are expected of every student.'], ['status' => 'active']);
        $make('partner', ['title' => 'Versatilo Group', 'kind' => 'employer', 'country' => 'Bangladesh'], ['status' => 'approved']);
        $make('alumni_event', ['title' => 'Class of 2024 reunion', 'starts_at' => $at(60, 16), 'kind' => 'reunion']);
        $make('credential', ['title' => 'Certificate of Excellence in Debate', 'kind' => 'achievement', 'starts_at' => $day(-30)], ['student_id' => $student->id]);

        // Library
        foreach ([['Higher Mathematics: First Paper', 'S. U. Ahmed', 'Mathematics', 6], ['Fundamentals of Physics', 'Halliday and Resnick', 'Physics', 3], ['A Brief History of Time', 'Stephen Hawking', 'Science', 2], ['অপরাজিত', 'বিভূতিভূষণ বন্দ্যোপাধ্যায়', 'Bangla', 4], ['English Grammar in Use', 'Raymond Murphy', 'English', 5]] as $i => [$title, $author, $category, $copies]) {
            $id = DB::table('library_books')->insertGetId(['school_id' => $school, 'title' => $title, 'author' => $author, 'category' => $category, 'shelf' => 'A' . ($i + 1), 'copies' => $copies, 'created_at' => now(), 'updated_at' => now()]);
            if ($i < 2) {
                DB::table('library_loans')->insert(['book_id' => $id, 'student_id' => $student->id, 'school_id' => $school, 'status' => 'issued', 'issued_on' => $day($i ? -20 : -5), 'due_on' => $day($i ? -6 : 9), 'handled_by' => $librarian->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Quizzes: one open now, one finished.
        $physics = DB::table('subjects')->where('name', 'Physics')->where('class_id', $ctx->class_id)->value('id');
        foreach ([['Vectors: before class', 'preparatory', -1, 6], ['Vectors: check your understanding', 'assimilation', -9, -8]] as [$title, $kind, $opens, $closes]) {
            $quiz = DB::table('quizzes')->insertGetId(['school_id' => $school, 'class_id' => $ctx->class_id, 'section_id' => $ctx->section_id, 'subject_id' => $physics, 'teacher_id' => $teacher->id, 'title' => $title, 'kind' => $kind, 'instructions' => 'Answer every question. You have one attempt.', 'opens_at' => now()->addDays($opens), 'closes_at' => now()->addDays($closes), 'duration_minutes' => 10, 'created_at' => now(), 'updated_at' => now()]);
            foreach ([
                ['choice', 'Which of these is a vector quantity?', ['Speed', 'Velocity', 'Mass', 'Time'], 1],
                ['true_false', 'Two vectors of equal size always point the same way.', null, false],
                ['numeric', 'Find the size of the resultant of 3 N and 4 N at right angles.', null, 5],
                ['multiple', 'Choose the scalar quantities.', ['Energy', 'Force', 'Temperature', 'Acceleration'], [0, 2]],
            ] as $n => [$type, $prompt, $options, $answer]) {
                DB::table('quiz_questions')->insert(['quiz_id' => $quiz, 'position' => $n + 1, 'type' => $type, 'prompt' => $prompt, 'options' => $options ? json_encode($options) : null, 'answer' => json_encode($answer), 'marks' => 1]);
            }
            if ($closes < 0) {
                DB::table('quiz_attempts')->insert(['quiz_id' => $quiz, 'student_id' => $student->id, 'started_at' => now()->addDays($opens), 'expires_at' => now()->addDays($opens)->addMinutes(10), 'submitted_at' => now()->addDays($opens)->addMinutes(6), 'answers' => json_encode((object) []), 'score' => 3, 'max_score' => 4]);
            }
        }

        // Elections: one open for voting.
        $election = DB::table('elections')->insertGetId(['school_id' => $school, 'class_id' => $ctx->class_id, 'title' => 'Prefect election 2027', 'position' => 'Class Prefect', 'description' => 'One vote for each student. Voting is secret.', 'opens_at' => now()->subHours(2), 'closes_at' => now()->addDays(3), 'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        foreach ($others->slice(1, 3) as $i => $candidate) {
            DB::table('election_candidates')->insert(['election_id' => $election, 'student_id' => $candidate->id, 'statement' => ['I will start a peer study circle.', 'I will make sure every complaint is heard.', 'I will organise a monthly clean-up day.'][$i % 3], 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        }

        // A conversation
        $thread = DB::table('conversations')->insertGetId(['school_id' => $school, 'student_id' => $student->id, 'member_id' => $guardian->id, 'staff_id' => $teacher->id, 'topic' => 'Physics homework', 'last_message_at' => now()->subHour(), 'created_at' => now(), 'updated_at' => now()]);
        foreach ([[$guardian->id, 'Assalamu alaikum. How is Nusrat doing with the vector problems?'], [$teacher->id, 'Walaikum assalam. Nusrat is doing very well. A little more practice with resolving components will help.']] as [$from, $body]) {
            DB::table('conversation_messages')->insert(['conversation_id' => $thread, 'sender_id' => $from, 'body' => $body, 'created_at' => now()->subHour(), 'updated_at' => now()]);
        }
    }

    private function account(string $email, string $name, int $role, int $school): User
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => $name, 'role_id' => $role, 'school_id' => $school, 'status' => 1, 'user_type' => 'regular', 'account_status' => 'enable',
            'password' => Hash::make(CampusDemoSeeder::PASSWORD),
            'user_information' => json_encode(['gender' => '', 'blood_group' => '', 'birthday' => '', 'phone' => '', 'address' => '', 'photo' => '']),
        ]);
    }
}
