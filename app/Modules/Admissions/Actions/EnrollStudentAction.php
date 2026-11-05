<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Events\StudentEnrolled;
use App\Modules\Admissions\Models\AcademicSection;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\GuardianProfile;
use App\Modules\Admissions\Models\StudentEnrollment;
use App\Modules\Admissions\Models\StudentGuardian;
use App\Modules\Admissions\Models\StudentProfile;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Admissions\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollStudentAction
{
    public function __construct(
        protected NumberSequenceService $numbers,
        protected ApplicationStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    /**
     * Idempotent: a second call for an enrolled application returns the same student profile.
     */
    public function execute(Application $application, AcademicSection $section, User $enroller, ?string $rollNo = null): StudentProfile
    {
        $application->refresh();

        if ($application->application_status === ApplicationStatus::ENROLLED && $application->enrolled_student_id) {
            return StudentProfile::findOrFail($application->enrolled_student_id);
        }

        $latestDecision = $application->latestDecision;
        if ($latestDecision && (int) $latestDecision->decided_by === (int) $enroller->id) {
            throw ValidationException::withMessages([
                'enroller' => 'Separation of duties violation: the council member who decided this application cannot enrol the candidate.',
            ]);
        }

        if ($application->application_status !== ApplicationStatus::ADMISSION_FEE_PAID) {
            throw ValidationException::withMessages([
                'status' => 'The admission fee must be paid before enrolment.',
            ]);
        }

        $paid = $application->payments()
            ->where('payment_type', PaymentType::ADMISSION_FEE->value)
            ->where('payment_status', PaymentStatus::SUCCESS->value)
            ->exists();
        if (!$paid) {
            throw ValidationException::withMessages(['payment' => 'No successful admission fee payment was found.']);
        }

        if (!$application->qrIsValid()) {
            throw ValidationException::withMessages(['qr' => 'The admission pass is expired or has already been used.']);
        }

        $program = $application->program;
        if ($section->class_id !== $program->class_id
            || $section->academic_year_id !== $program->academic_year_id
            || ($program->group_id && $section->group_id !== $program->group_id)) {
            throw ValidationException::withMessages([
                'section_id' => 'This section does not belong to the candidate\'s class, group or academic year.',
            ]);
        }

        [$profile, $enrollment, $application] = DB::transaction(function () use ($application, $section, $enroller, $rollNo, $program) {
            $application = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $section = AcademicSection::whereKey($section->id)->lockForUpdate()->firstOrFail();

            if ($section->availableSeatsCount() <= 0) {
                throw ValidationException::withMessages([
                    'section_id' => "Section {$section->name} is full (capacity {$section->capacity}).",
                ]);
            }

            $studentNumber = $this->numbers->generateStudentId($program->section_type->value);

            // Initial password is random and never shown. The applicant sets their own password (set-password flow: not yet built).
            $studentUser = User::create([
                'name' => $application->applicant_name,
                'email' => $this->uniqueEmail($application->email, 'student', $studentNumber),
                'password' => Hash::make(Str::random(48)),
                'phone_number' => $application->mobile,
                'user_type' => 'regular',
                'role_id' => 7,
                'student_id' => $studentNumber,
                'status' => 1,
                'school_id' => 1,
            ]);
            $this->assignRole($studentUser, 'Student');

            $profile = StudentProfile::create([
                'user_id' => $studentUser->id,
                'student_number' => $studentNumber,
                'blood_group' => $application->blood_group,
                'dob' => $application->dob,
                'gender' => $application->gender,
                'nationality' => 'Bangladeshi',
                'current_address' => $application->current_address,
            ]);

            foreach ($application->guardians as $appGuardian) {
                $guardianProfile = $this->findOrCreateGuardian($appGuardian);

                StudentGuardian::firstOrCreate(
                    ['student_id' => $profile->id, 'guardian_id' => $guardianProfile->id],
                    ['is_primary' => (bool) $appGuardian->is_primary]
                );
            }

            $enrollment = StudentEnrollment::create([
                'student_id' => $profile->id,
                'academic_year_id' => $program->academic_year_id,
                'class_id' => $program->class_id,
                'group_id' => $program->group_id,
                'section_id' => $section->id,
                'roll_no' => $rollNo,
                'status' => 'ACTIVE',
                'enrolled_on' => now()->toDateString(),
            ]);

            $application->forceFill([
                'enrolled_student_id' => $profile->id,
                'qr_used_at' => now(),
            ])->save();

            $this->stateMachine->transition(
                $application,
                ApplicationStatus::ENROLLED,
                $enroller,
                "Enrolled with Student ID {$studentNumber} in section {$section->name}."
            );

            $this->audit->record('student_enrolled', $application->id, [], [
                'student_number' => $studentNumber,
                'section_id' => $section->id,
                'enrollment_id' => $enrollment->id,
            ], $enroller);

            return [$profile, $enrollment, $application];
        });

        event(new StudentEnrolled($profile, $enrollment, $application));

        return $profile;
    }

    /** Guardians are matched by phone AND guardian role, so a student's own number can never resolve to the student. */
    private function findOrCreateGuardian($appGuardian): GuardianProfile
    {
        $existing = GuardianProfile::query()
            ->where('emergency_contact', $appGuardian->mobile)
            ->whereHas('user', fn ($q) => $q->where('role_id', 6))
            ->first();

        if ($existing) {
            return $existing;
        }

        $user = User::create([
            'name' => $appGuardian->name,
            'email' => $this->uniqueEmail($appGuardian->email, 'guardian', Str::random(8)),
            'password' => Hash::make(Str::random(48)),
            'phone_number' => $appGuardian->mobile,
            'user_type' => 'regular',
            'role_id' => 6,
            'status' => 1,
            'school_id' => 1,
        ]);
        $this->assignRole($user, 'Guardian');

        return GuardianProfile::create([
            'user_id' => $user->id,
            'relation' => $appGuardian->relation,
            'occupation' => $appGuardian->occupation,
            'nid_passport' => $appGuardian->nid_passport,
            'emergency_contact' => $appGuardian->mobile,
        ]);
    }

    private function uniqueEmail(?string $preferred, string $kind, string $suffix): string
    {
        if ($preferred && !User::where('email', $preferred)->exists()) {
            return $preferred;
        }

        return strtolower($kind . '.' . $suffix . '@mcc.edu.bd');
    }

    private function assignRole(User $user, string $role): void
    {
        if (\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
            $user->assignRole($role);
        }
    }
}
