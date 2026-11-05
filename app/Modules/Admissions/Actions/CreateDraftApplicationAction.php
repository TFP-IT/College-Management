<?php

namespace App\Modules\Admissions\Actions;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Admissions\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDraftApplicationAction
{
    public function __construct(
        protected NumberSequenceService $numbers,
        protected AuditService $audit
    ) {}

    public function execute(AdmissionProgram $program, array $initialData = [], ?User $user = null): Application
    {
        if (!$program->isOpen()) {
            throw ValidationException::withMessages([
                'program_id' => 'This admission programme is closed or past its deadline.',
            ]);
        }

        $mobile = $initialData['phone'] ?? $initialData['mobile'] ?? null;
        $dob = $initialData['candidate_dob'] ?? $initialData['dob'] ?? null;

        if ($mobile && $dob && Application::where('program_id', $program->id)->where('mobile', $mobile)->whereDate('dob', $dob)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'An application already exists for this candidate in this programme.',
            ]);
        }

        return DB::transaction(function () use ($program, $initialData, $user, $mobile, $dob) {
            $application = Application::create([
                'program_id' => $program->id,
                'application_no' => $this->numbers->generateApplicationNumber(),
                'applicant_user_id' => $user?->id,
                'applicant_name' => $initialData['candidate_name'] ?? 'Draft Candidate',
                'email' => $initialData['email'] ?? null,
                'mobile' => $mobile,
                'dob' => $dob,
                'gender' => $initialData['gender'] ?? null,
                'blood_group' => $initialData['blood_group'] ?? null,
                'current_address' => $initialData['current_address'] ?? null,
                'permanent_address' => $initialData['permanent_address'] ?? null,
                'previous_school' => $initialData['previous_school'] ?? null,
                'previous_gpa' => $initialData['previous_gpa'] ?? null,
                'academic_history' => $initialData['academic_history'] ?? [],
                'status' => ApplicationStatus::DRAFT->value,
            ]);

            $this->audit->record('draft_created', $application->id, [], ['application_no' => $application->application_no], $user);

            return $application->fresh();
        });
    }
}
