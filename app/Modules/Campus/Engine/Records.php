<?php

namespace App\Modules\Campus\Engine;

use App\Models\User;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Campus\Support\StudentContext;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Storage and rules shared by every module: validation, creation, status changes and presentation.
 * Every write is audited.
 */
class Records
{
    private const COLUMNS = ['title', 'starts_at', 'ends_at', 'amount'];

    public function __construct(private AuditService $audit, private Workflow $workflow, private Effects $effects)
    {
    }

    public function query(Module $module, int $schoolId): Builder
    {
        $query = DB::table($module->table() ?? 'campus_records')->where('school_id', $schoolId);

        return $module->table() ? $query : $query->where('type', $module->key);
    }

    /** Turns a stored row into one shape, whichever table it came from. */
    public function normalise(Module $module, object $row): object
    {
        if (!$module->table()) {
            $row->data = $row->data ? json_decode($row->data, true) : [];

            return $row;
        }

        $data = [];
        foreach (array_keys($module->fields()) as $key) {
            $data[$key] = $row->{$key} ?? null;
        }
        $title = (string) ($row->{$module->get('title_field')} ?? '');

        return (object) [
            'id' => $row->id, 'type' => $module->key, 'school_id' => $row->school_id, 'student_id' => null,
            'staff_id' => $row->user_id, 'parent_id' => null,
            'title' => $module->get('title_field') === 'period' ? Carbon::parse($title)->format('F Y') : $title,
            'status' => $row->status, 'starts_at' => null, 'ends_at' => null, 'amount' => null,
            'data' => $data, 'created_by' => null, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
        ];
    }

    public function find(Module $module, int $schoolId, int $id): object
    {
        $row = $this->query($module, $schoolId)->where('id', $id)->first();
        abort_unless($row, 404, 'Record not found.');

        return $this->normalise($module, $row);
    }

    /** @param string[]|null $only field keys the actor may set; null means every field */
    public function validate(Module $module, array $input, int $schoolId, ?array $only = null, bool $partial = false): array
    {
        $rules = [];
        $names = [];

        foreach ($module->fields() as $key => $field) {
            if ($only !== null && !in_array($key, $only, true)) {
                continue;
            }
            $required = $field['required'] && !$partial ? 'required' : ($partial ? 'sometimes' : 'nullable');
            $rules[$key] = array_merge([$required], match ($field['type']) {
                'textarea' => ['nullable', 'string', 'max:5000'],
                'date', 'datetime' => ['nullable', 'date', 'after:2000-01-01', 'before:2100-01-01'],
                'number' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
                'money' => ['nullable', 'integer', 'min:0', 'max:100000000'],
                'select' => ['nullable', Rule::in($field['options'])],
                'section' => ['nullable', 'string', 'regex:/^\d+-\d+$/'],
                default => ['nullable', 'string', 'max:200'],
            });
            if ($field['required'] && !$partial) {
                $rules[$key] = array_values(array_diff($rules[$key], ['nullable']));
            }
            $names[$key] = strtolower($field['label']);
        }

        $data = Validator::make($input, $rules, [], $names)->validate();

        if (!empty($data['starts_at']) && !empty($data['ends_at']) && Carbon::parse($data['ends_at'])->lt(Carbon::parse($data['starts_at']))) {
            throw ValidationException::withMessages(['ends_at' => 'The end date is before the start date.']);
        }

        return $data;
    }

    /**
     * @param array{student_id?:?int, staff_id?:?int, parent_id?:?int, status?:string, title?:string} $context
     */
    public function create(Module $module, array $fields, int $schoolId, ?User $actor, array $context = []): object
    {
        if ($module->subject() === 'student' && empty($context['student_id'])) {
            throw ValidationException::withMessages(['student_id' => 'Choose a student.']);
        }
        if ($module->subject() === 'staff' && empty($context['staff_id'])) {
            throw ValidationException::withMessages(['staff_id' => 'Choose a staff member.']);
        }
        if ($module->get('one_per_student') && $this->query($module, $schoolId)->where('student_id', $context['student_id'])->exists()) {
            throw ValidationException::withMessages(['student_id' => 'This student already has a ' . strtolower($module->get('label')) . '. Open it and edit it.']);
        }

        $id = DB::transaction(function () use ($module, $fields, $schoolId, $actor, $context) {
            $status = $context['status'] ?? $module->initialStatus();

            if ($module->table()) {
                $row = ['user_id' => $context['staff_id'], 'school_id' => $schoolId, 'status' => $status, 'created_at' => now(), 'updated_at' => now()];
                foreach ($module->fields() as $key => $field) {
                    if (isset($fields[$key]) && $fields[$key] !== '') {
                        $row[$key] = $fields[$key];
                    }
                }
                $row = $this->tableDefaults($module, $row);

                return DB::table($module->table())->insertGetId($row);
            }

            $columns = array_intersect_key($fields, array_flip(self::COLUMNS));
            foreach (['starts_at', 'ends_at'] as $date) {
                if (!empty($columns[$date])) {
                    $columns[$date] = Carbon::parse($columns[$date])->setTimezone(config('app.timezone'));
                }
            }

            return DB::table('campus_records')->insertGetId([
                'school_id' => $schoolId,
                'type' => $module->key,
                'student_id' => $context['student_id'] ?? null,
                'staff_id' => $context['staff_id'] ?? null,
                'parent_id' => $context['parent_id'] ?? null,
                'title' => mb_substr((string) ($context['title'] ?? $columns['title'] ?? $module->get('label')), 0, 200),
                'status' => $status,
                'starts_at' => $columns['starts_at'] ?? null,
                'ends_at' => $columns['ends_at'] ?? null,
                'amount' => $columns['amount'] ?? null,
                'data' => json_encode(array_diff_key($fields, array_flip(self::COLUMNS))),
                'created_by' => $actor?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $record = $this->find($module, $schoolId, $id);
        $this->audit->record("{$module->key}.created", (string) $id, [], ['title' => $record->title, 'status' => $record->status], $actor, 'campus');

        if ($steps = $module->get('workflow')) {
            $this->workflow->open($module, $record, $steps, $actor);
        }
        $this->effects->created($module, $record, $actor);

        return $this->find($module, $schoolId, $id);
    }

    public function update(Module $module, object $record, array $fields, ?User $actor): object
    {
        $changes = ['updated_at' => now()];

        if ($module->table()) {
            foreach (array_intersect_key($fields, $module->fields()) as $key => $value) {
                $changes[$key] = $value === '' ? null : $value;
            }
            $changes = $this->tableDefaults($module, $changes + array_intersect_key((array) $record->data, ['basic' => 1, 'allowances' => 1, 'deductions' => 1]));
        } else {
            $data = $record->data;
            foreach ($fields as $key => $value) {
                if (in_array($key, self::COLUMNS, true)) {
                    $changes[$key] = in_array($key, ['starts_at', 'ends_at'], true) && $value
                        ? Carbon::parse($value)->setTimezone(config('app.timezone'))
                        : ($key === 'title' ? ($value ?: $record->title) : $value);
                } else {
                    $data[$key] = $value;
                }
            }
            $changes['data'] = json_encode($data);
        }

        $this->query($module, $record->school_id)->where('id', $record->id)->update($changes);
        $this->audit->record("{$module->key}.updated", (string) $record->id, array_intersect_key((array) $record->data, $fields), $module->get('sensitive') ? ['fields' => array_keys($fields)] : $fields, $actor, 'campus');

        $fresh = $this->find($module, $record->school_id, $record->id);
        $this->effects->saved($module, $fresh);

        return $fresh;
    }

    public function setStatus(Module $module, object $record, string $status, ?User $actor): object
    {
        if (!array_key_exists($status, $module->get('statuses'))) {
            throw ValidationException::withMessages(['status' => 'That status is not available.']);
        }
        if ($status === $record->status) {
            return $record;
        }

        $changes = ['status' => $status, 'updated_at' => now()];
        if ($module->table() === 'staff_payslips' && $status === 'paid') {
            $changes['paid_at'] = now();
        }

        $this->query($module, $record->school_id)->where('id', $record->id)->update($changes);
        $this->audit->record("{$module->key}.status", (string) $record->id, ['status' => $record->status], ['status' => $status], $actor, 'campus');

        $fresh = $this->find($module, $record->school_id, $record->id);
        $this->effects->statusChanged($module, $fresh, $actor);

        return $this->find($module, $record->school_id, $record->id);
    }

    /** Reading a sensitive record is itself recorded. */
    public function viewed(Module $module, object $record, User $viewer): void
    {
        if ($module->get('sensitive')) {
            $this->audit->record("{$module->key}.viewed", (string) $record->id, [], [], $viewer, 'campus');
        }
    }

    public function present(Module $module, object $record, bool $private = false, ?array $names = null): array
    {
        $names ??= User::whereIn('id', array_filter([$record->student_id, $record->staff_id]))->pluck('name', 'id')->all();
        $hidden = $private ? [] : $module->get('private_fields', []);
        $fields = [];

        foreach ($module->fields() as $key => $field) {
            if (in_array($key, $hidden, true)) {
                continue;
            }
            $value = in_array($key, self::COLUMNS, true) && !$module->table() ? ($record->{$key} ?? null) : ($record->data[$key] ?? null);
            if ($value === null || $value === '') {
                continue;
            }
            if (in_array($field['type'], ['date', 'datetime'], true)) {
                $value = Carbon::parse($value)->format($field['type'] === 'date' ? 'j M Y' : 'j M Y, g:i A');
            } elseif ($field['type'] === 'money') {
                $value = '৳' . number_format((float) $value);
            } elseif ($field['type'] === 'select') {
                $value = ucfirst((string) $value);
            }
            if ($key !== 'title') {
                $fields[] = ['key' => $key, 'label' => $field['label'], 'value' => (string) $value];
            }
        }

        return [
            'id' => (int) $record->id,
            'type' => $module->key,
            'title' => $record->title,
            'status' => $record->status,
            'status_label' => $module->statusLabel($record->status)['label'],
            'tone' => $module->statusLabel($record->status)['tone'],
            'student_id' => $record->student_id ? (int) $record->student_id : null,
            'student' => $record->student_id ? ($names[$record->student_id] ?? null) : null,
            'staff_id' => $record->staff_id ? (int) $record->staff_id : null,
            'staff' => $record->staff_id ? ($names[$record->staff_id] ?? null) : null,
            'starts_at' => $record->starts_at ? Carbon::parse($record->starts_at)->toIso8601String() : null,
            'ends_at' => $record->ends_at ? Carbon::parse($record->ends_at)->toIso8601String() : null,
            'amount' => $record->amount === null ? null : (int) $record->amount,
            'created_at' => $record->created_at ? Carbon::parse($record->created_at)->toIso8601String() : null,
            'fields' => $fields,
        ];
    }

    /** People who can be chosen as the staff member on a record. */
    public function staffOptions(int $schoolId): array
    {
        return User::where('school_id', $schoolId)->where('status', 1)->whereIn('role_id', [2, 3, 4, 5])
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    private function tableDefaults(Module $module, array $row): array
    {
        if ($module->table() === 'staff_payslips') {
            $row['net'] = max(0, (float) ($row['basic'] ?? 0) + (float) ($row['allowances'] ?? 0) - (float) ($row['deductions'] ?? 0));
            foreach (['basic', 'allowances', 'deductions'] as $key) {
                $row[$key] = $row[$key] ?? 0;
            }
            if (!empty($row['period'])) {
                $row['period'] = Carbon::parse($row['period'])->startOfMonth()->toDateString();
                if (!isset($row['updated_at_only']) && isset($row['user_id']) && DB::table('staff_payslips')->where('user_id', $row['user_id'])->whereDate('period', $row['period'])->exists()) {
                    throw ValidationException::withMessages(['period' => 'A payslip for this month already exists.']);
                }
            }
        }
        if ($module->table() === 'staff_trainings') {
            $row['hours'] = $row['hours'] ?? 0;
        }

        return array_intersect_key($row, array_flip(Schema::getColumnListing($module->table())));
    }
}
