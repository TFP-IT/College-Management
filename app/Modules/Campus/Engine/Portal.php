<?php

namespace App\Modules\Campus\Engine;

use App\Models\User;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** What students, guardians and teachers may do with a module from the app. */
class Portal
{
    public function __construct(private Records $records)
    {
    }

    public static function kind(User $user): ?string
    {
        return match ((int) $user->role_id) {
            6, 7 => 'family',
            3 => 'teacher',
            default => null,
        };
    }

    public function visible(User $user, Module $module): bool
    {
        $kind = self::kind($user);
        if (!$kind) {
            return false;
        }
        if ($module->has('broadcast')) {
            // Staff-facing lists are not offered to families, and the reverse.
            return !in_array($module->key, $kind === 'teacher' ? ['scholarship_program'] : [], true);
        }
        if ($kind === 'teacher') {
            return $module->subject() === 'staff' || $module->has('teacher');
        }

        return $module->has('family');
    }

    /** @return string[] fields this person may fill in; empty when they cannot create */
    public function creatable(User $user, Module $module): array
    {
        $kind = self::kind($user);

        return $kind ? $module->get("{$kind}.create", []) : [];
    }

    private function child(User $user, ?int $studentId): User
    {
        $student = $studentId ? User::find($studentId) : ((int) $user->role_id === 7 ? $user : null);
        abort_unless($student && StudentContext::isFamily($user, $student), 404, 'Student not found.');

        return $student;
    }

    /** Records this person may see, newest first. */
    public function query(User $user, Module $module, ?int $studentId)
    {
        $school = (int) $user->school_id;
        $query = $this->records->query($module, $school);
        $kind = self::kind($user);
        abort_unless($this->visible($user, $module), 404);

        if ($module->has('broadcast')) {
            return $query->whereIn('status', $module->get('broadcast'));
        }

        if ($module->table()) {
            // Staff files: only your own, and never a draft payslip.
            return $query->where('user_id', $user->id)->where('status', '!=', 'draft');
        }

        if ($kind === 'family') {
            $student = $this->child($user, $studentId);
            $query->where('student_id', $student->id);
            if ($statuses = $module->get('family.view_statuses')) {
                $query->whereIn('status', $statuses);
            }
            if ($module->get('family.own_only')) {
                $query->where('created_by', $user->id);
            }

            return $query;
        }

        if ($module->get('teacher.view') === 'assigned') {
            return $query->where('staff_id', $user->id);
        }

        return $module->subject() === 'staff'
            ? $query->where('staff_id', $user->id)
            : $query->where('created_by', $user->id);
    }

    public function find(User $user, Module $module, int $id, ?int $studentId): object
    {
        $row = $this->query($user, $module, $studentId)->where('id', $id)->first();
        abort_unless($row, 404, 'Record not found.');

        return $this->records->normalise($module, $row);
    }

    public function create(User $user, Module $module, array $input): object
    {
        $allowed = $this->creatable($user, $module);
        abort_unless($allowed, 403, 'You cannot add this.');

        $fields = $this->records->validate($module, $input, (int) $user->school_id, array_values(array_diff($allowed, ['staff_id'])));
        $context = [];

        if ($module->subject() === 'student') {
            if (self::kind($user) === 'family') {
                $context['student_id'] = $this->child($user, (int) ($input['student_id'] ?? 0) ?: null)->id;
            } else {
                $student = User::find((int) ($input['student_id'] ?? 0));
                abort_unless($student && StudentContext::canView($user, $student), 404, 'Choose one of your students.');
                $context['student_id'] = $student->id;
            }
        }
        if ($module->subject() === 'staff') {
            $context['staff_id'] = $user->id;
        }
        if (in_array('staff_id', $allowed, true)) {
            $options = $this->contacts($user, User::findOrFail($context['student_id']));
            $staff = (int) ($input['staff_id'] ?? 0);
            if (!isset($options[$staff])) {
                throw ValidationException::withMessages(['staff_id' => 'Choose who you want to meet.']);
            }
            $context['staff_id'] = $staff;
        }
        // Titles that are choices are shown with their readable name.
        if (isset($fields['title'])) {
            $context['title'] = $module->fields()['title']['type'] === 'select' ? ucfirst($fields['title']) : $fields['title'];
        }

        return $this->records->create($module, $fields, (int) $user->school_id, $user, $context);
    }

    /** Signing up to a published item: a club, route, competition, track or scholarship. */
    public function join(User $user, Module $parentModule, int $parentId, array $input): object
    {
        $join = $parentModule->get('join');
        abort_unless($join && self::kind($user) === 'family', 404);
        $student = $this->child($user, (int) ($input['student_id'] ?? 0) ?: null);

        $parent = $this->records->find($parentModule, (int) $user->school_id, $parentId);
        $openStatus = $parentModule->get('broadcast')[0];
        $expired = $parent->ends_at && \Carbon\Carbon::parse($parent->ends_at)->endOfDay()->isPast();
        abort_if($parent->status !== $openStatus || $expired, 422, 'This is closed for new requests.');

        $module = Module::find($join['type']);
        $existing = $this->records->query($module, (int) $user->school_id)->where('student_id', $student->id)->where('parent_id', $parent->id)
            ->whereNotIn('status', ['rejected', 'withdrawn', 'left', 'ended', 'cancelled'])->exists();
        abort_if($existing, 422, 'You have already sent a request for this.');

        if ($seatField = $join['capacity_field'] ?? null) {
            $seats = (int) ($parent->data[$seatField] ?? 0);
            $taken = DB::table('campus_records')->where('parent_id', $parent->id)->where('status', 'approved')->count();
            abort_if($seats > 0 && $taken >= $seats, 422, 'All places are taken.');
        }

        $keys = array_values(array_diff($module->get('family.create', []), ['title']));
        $fields = $this->records->validate($module, $input, (int) $user->school_id, $keys);
        $fields['title'] = $parent->title;
        if ($module->key === 'transport_application' && $parent->amount) {
            $fields['amount'] = $parent->amount;
        }

        return $this->records->create($module, $fields, (int) $user->school_id, $user, [
            'student_id' => $student->id, 'parent_id' => $parent->id, 'title' => $parent->title,
        ]);
    }

    /** Changes a person may make to a record that already exists. */
    public function change(User $user, Module $module, int $id, array $input): object
    {
        $kind = self::kind($user);
        $record = $this->find($user, $module, $id, (int) ($input['student_id'] ?? 0) ?: null);
        $status = $input['status'] ?? null;
        $fields = $kind === 'family'
            ? ($module->get('family.edit') ? $module->get('family.create', []) : [])
            : $module->get('teacher.update', []);

        if ($status !== null) {
            abort_unless(in_array($status, $module->get("{$kind}.status", []), true), 403, 'You cannot set that status.');
        }

        $changes = array_intersect_key($input, array_flip($fields));
        abort_unless($status !== null || $changes, 403, 'There is nothing here you can change.');

        if ($changes) {
            $changes = $this->records->validate($module, $changes, (int) $user->school_id, array_keys($changes), true);
            $record = $this->records->update($module, $record, $changes, $user);
        }

        return $status !== null ? $this->records->setStatus($module, $record, $status, $user) : $record;
    }

    /** @return array<int, string> staff a family may contact about this student: the class teachers and the office */
    public function contacts(User $user, User $student): array
    {
        $context = StudentContext::for($student);

        $teachers = DB::table('routines')->join('users', 'users.id', '=', 'routines.teacher_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'routines.subject_id')
            ->where('routines.class_id', $context->classId ?? 0)->where('routines.section_id', $context->sectionId ?? 0)
            ->where('users.status', 1)->orderBy('users.name')
            ->get(['users.id', 'users.name', 'subjects.name as subject'])
            ->groupBy('id')
            ->mapWithKeys(fn ($rows, $id) => [(int) $id => $rows->first()->name . ', ' . $rows->pluck('subject')->unique()->filter()->implode(', ')]);

        $office = User::where('school_id', $student->school_id)->where('status', 1)->whereIn('role_id', [2, 4, 5])->orderBy('role_id')->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->name . ', ' . [2 => 'College office', 4 => 'Accounts', 5 => 'Library'][$u->role_id]]);

        $coordinators = User::where('school_id', $student->school_id)->where('status', 1)->whereHas('roles', fn ($q) => $q->whereIn('name', ['Principal', 'Counsellor', 'Career Coordinator']))->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->name . ', ' . $u->getRoleNames()->first()]);

        return $teachers->union($office)->union($coordinators)->all();
    }
}
