<?php

namespace App\Modules\Campus\Support;

use App\Models\Admission;
use App\Models\User;
use App\Services\TeacherClassroomService;
use Illuminate\Support\Facades\DB;

/**
 * Where a student sits (class, section, session) and who may see their record.
 * Every campus screen starts here, so the access rules live in one place.
 */
class StudentContext
{
    public const STUDENT = 7;
    public const GUARDIAN = 6;
    public const TEACHER = 3;

    private function __construct(
        public readonly User $student,
        public readonly int $schoolId,
        public readonly ?int $sessionId,
        public readonly ?int $classId,
        public readonly ?int $sectionId,
        public readonly ?string $className,
        public readonly ?string $sectionName,
    ) {
    }

    public static function for(User $student): self
    {
        $schoolId = (int) $student->school_id;
        $sessionId = self::runningSession($schoolId);
        $enrollment = DB::table('enrollments')->where('user_id', $student->id)->orderByDesc('id')->first();

        $classId = $enrollment ? (int) $enrollment->class_id : null;
        $sectionId = $enrollment ? (int) $enrollment->section_id : null;
        $section = $sectionId ? DB::table('sections')->where('id', $sectionId)->first() : null;

        // Some older enrolments point at the same-named section of another class.
        if ($section && (int) $section->class_id !== $classId) {
            $sectionId = (int) (DB::table('sections')->where('class_id', $classId)->where('name', $section->name)->value('id') ?: $sectionId);
        }

        return new self(
            $student,
            $schoolId,
            $sessionId,
            $classId,
            $sectionId,
            $classId ? DB::table('classes')->where('id', $classId)->value('name') : null,
            $section?->name,
        );
    }

    public static function runningSession(int $schoolId): ?int
    {
        $id = DB::table('schools')->where('id', $schoolId)->value('running_session');

        return $id ? (int) $id : null;
    }

    public function enrolled(): bool
    {
        return $this->classId !== null;
    }

    /** The student, their guardian, school administrators, or a teacher of their class. */
    public static function canView(User $viewer, User $student): bool
    {
        if ((int) $student->role_id !== self::STUDENT) {
            return false;
        }

        if (self::isFamily($viewer, $student)) {
            return true;
        }

        $role = (int) $viewer->role_id;
        if ($role === 1) {
            return true;
        }
        if ((int) $viewer->school_id !== (int) $student->school_id) {
            return false;
        }
        if ($role === 2) {
            return true;
        }

        if ($role === self::TEACHER) {
            $ctx = self::for($student);

            return app(TeacherClassroomService::class)->classes($viewer)
                ->contains(fn ($c) => $c['class_id'] === $ctx->classId && $c['section_id'] === $ctx->sectionId);
        }

        return false;
    }

    /** The people who act for a student: the student and the linked guardian. */
    public static function isFamily(User $viewer, User $student): bool
    {
        if ($viewer->id === $student->id) {
            return true;
        }

        return (int) $viewer->role_id === self::GUARDIAN
            && ((int) $student->parent_id === (int) $viewer->id
                || Admission::where('user_id', $student->id)->where('guardian_user_id', $viewer->id)->exists());
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    public static function childrenOf(User $guardian)
    {
        return User::where('role_id', self::STUDENT)
            ->where('status', 1)
            ->where(function ($q) use ($guardian) {
                $q->where('parent_id', $guardian->id)
                    ->orWhereIn('id', Admission::where('guardian_user_id', $guardian->id)->whereNotNull('user_id')->select('user_id'));
            })
            ->orderBy('name')
            ->get();
    }

    public static function photoUrl(User $user): ?string
    {
        $info = json_decode((string) $user->user_information, true);
        if (!empty($info['photo'])) {
            return asset('assets/uploads/user-images/' . $info['photo']);
        }

        return Admission::where('user_id', $user->id)->latest('id')->first()?->photo_url;
    }
}
