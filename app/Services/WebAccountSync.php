<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes accounts created from the mobile app usable in the web admin:
 * - teachers, guardians and students get the profile fields the web pages read;
 * - each student is enrolled in the class written on their admission.
 * Existing web data (profile values, enrollments) is never overwritten.
 */
class WebAccountSync
{
    public const TEACHER_ROLE = 3;
    public const GUARDIAN_ROLE = 6;
    public const STUDENT_ROLE = 7;

    private const PROFILE_DEFAULTS = [
        'gender' => '',
        'blood_group' => '',
        'birthday' => '',
        'phone' => '',
        'address' => '',
        'photo' => '',
    ];

    public function syncUser(User $user): void
    {
        if (!in_array((int) $user->role_id, [self::TEACHER_ROLE, self::GUARDIAN_ROLE, self::STUDENT_ROLE], true)) {
            return;
        }

        $this->fillProfile($user);

        if ((int) $user->role_id === self::STUDENT_ROLE) {
            $this->enrollStudent($user);
        }
    }

    public function syncAdmission(Admission $admission): void
    {
        $student = $admission->user_id ? User::find($admission->user_id) : null;
        if ($student && (int) $student->role_id === self::STUDENT_ROLE) {
            $this->enrollStudent($student, $admission);
        }
    }

    /** Called after the phone app records a paid admission fee. */
    public function enrollAfterAdmissionFee(User $student): string
    {
        return $this->enrollStudent($student);
    }

    /**
     * @return array{users:int, profiles:int, enrolled:int, skipped:array<int,string>}
     */
    public function syncAll(): array
    {
        $result = ['users' => 0, 'profiles' => 0, 'enrolled' => 0, 'skipped' => []];

        User::whereIn('role_id', [self::TEACHER_ROLE, self::GUARDIAN_ROLE, self::STUDENT_ROLE])
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$result) {
                foreach ($users as $user) {
                    $result['users']++;
                    if ($this->fillProfile($user)) {
                        $result['profiles']++;
                    }
                    if ((int) $user->role_id === self::STUDENT_ROLE) {
                        $outcome = $this->enrollStudent($user);
                        if ($outcome === 'enrolled') {
                            $result['enrolled']++;
                        } elseif ($outcome !== 'exists') {
                            $result['skipped'][$user->id] = "{$user->name}: {$outcome}";
                        }
                    }
                }
            });

        return $result;
    }

    private function fillProfile(User $user): bool
    {
        $info = json_decode((string) $user->user_information, true);
        $info = is_array($info) ? $info : [];

        $filled = $info + self::PROFILE_DEFAULTS;
        if ($filled['phone'] === '' && $user->phone_number) {
            $filled['phone'] = $user->phone_number;
        }

        if ($filled === $info) {
            return false;
        }

        $user->forceFill(['user_information' => json_encode($filled)])->saveQuietly();

        return true;
    }

    private function enrollStudent(User $student, ?Admission $admission = null): string
    {
        if (DB::table('enrollments')->where('user_id', $student->id)->exists()) {
            return 'exists';
        }

        $admission ??= Admission::where('user_id', $student->id)->latest('id')->first();
        if ($admission && !$this->admissionFeePaid($admission)) {
            return 'admission fee not paid';
        }
        $className = trim((string) $admission?->class_name);
        if ($className === '') {
            return 'no class on the admission';
        }

        $schoolId = (int) ($student->school_id ?: 1);
        $sessionId = $this->runningSession($schoolId);
        if (!$sessionId) {
            return 'no running session for the school';
        }

        DB::transaction(function () use ($student, $className, $schoolId, $sessionId) {
            $classId = DB::table('classes')
                ->where('school_id', $schoolId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($className)])
                ->value('id');
            if (!$classId) {
                $classId = DB::table('classes')->insertGetId([
                    'name' => $className, 'school_id' => $schoolId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $sectionId = DB::table('sections')->where('class_id', $classId)->orderBy('id')->value('id');
            if (!$sectionId) {
                $sectionId = DB::table('sections')->insertGetId([
                    'name' => 'A', 'class_id' => $classId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            DB::table('enrollments')->insert([
                'user_id' => $student->id,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'school_id' => $schoolId,
                'department_id' => 0,
                'session_id' => $sessionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return 'enrolled';
    }

    private function admissionFeePaid(Admission $admission): bool
    {
        return $admission->payments()
            ->where('status', 'paid')
            ->where(function ($query) {
                $query->where('type', 'admission_fee')->orWhereNotNull('admission_fee');
            })
            ->exists();
    }

    private function runningSession(int $schoolId): ?int
    {
        $running = Schema::hasColumn('schools', 'running_session')
            ? DB::table('schools')->where('id', $schoolId)->value('running_session')
            : null;

        return $running ? (int) $running : (DB::table('sessions')->where('status', 1)->orderByDesc('id')->value('id') ?: null);
    }
}
