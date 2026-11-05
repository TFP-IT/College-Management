<?php

namespace App\Modules\Admissions\Actions;

use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationGuardian;
use App\Modules\Admissions\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDraftApplicationAction
{
    /** request key => column */
    private const FIELDS = [
        'candidate_name' => 'applicant_name',
        'email' => 'email',
        'phone' => 'mobile',
        'candidate_dob' => 'dob',
        'gender' => 'gender',
        'blood_group' => 'blood_group',
        'current_address' => 'current_address',
        'permanent_address' => 'permanent_address',
        'previous_school' => 'previous_school',
        'previous_gpa' => 'previous_gpa',
        'academic_history' => 'academic_history',
    ];

    public function __construct(private AuditService $audit) {}

    public function execute(Application $application, array $data, $actor = null): Application
    {
        if ($application->application_status !== ApplicationStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Only draft applications can be changed.',
            ]);
        }

        return DB::transaction(function () use ($application, $data, $actor) {
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            $update = [];
            foreach (self::FIELDS as $key => $column) {
                if (array_key_exists($key, $data)) {
                    $update[$column] = $data[$key];
                }
            }
            if ($update) {
                $locked->update($update);
            }

            if (isset($data['guardians']) && is_array($data['guardians'])) {
                $this->syncGuardians($locked, $data['guardians']);
            }

            $this->audit->record('draft_updated', $locked->id, [], array_keys($update), $actor);

            $application->setRawAttributes($locked->fresh()->getAttributes(), true);

            return $application->fresh(['guardians', 'documents', 'program']);
        });
    }

    /** Replaces the guardian list. Exactly one guardian is primary; the first is used if none is marked. */
    private function syncGuardians(Application $application, array $guardians): void
    {
        $application->guardians()->delete();

        $hasPrimary = collect($guardians)->contains(fn ($g) => !empty($g['is_primary']));

        foreach (array_values($guardians) as $index => $g) {
            ApplicationGuardian::create([
                'application_id' => $application->id,
                'name' => $g['full_name'] ?? '',
                'relation' => $g['relation'] ?? 'FATHER',
                'mobile' => $g['phone'] ?? '',
                'email' => $g['email'] ?? null,
                'occupation' => $g['occupation'] ?? null,
                'nid_passport' => $g['nid_passport'] ?? null,
                'is_primary' => $hasPrimary ? (bool) ($g['is_primary'] ?? false) : $index === 0,
            ]);
        }
    }
}
