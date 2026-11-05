<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Actions\CreateDraftApplicationAction;
use App\Modules\Admissions\Actions\SubmitApplicationAction;
use App\Modules\Admissions\Actions\UpdateDraftApplicationAction;
use App\Modules\Admissions\Actions\WithdrawApplicationAction;
use App\Modules\Admissions\Enums\DocType;
use App\Modules\Admissions\Http\Requests\ApplyProgramRequest;
use App\Modules\Admissions\Http\Requests\UpdateApplicationRequest;
use App\Modules\Admissions\Http\Requests\UploadDocumentRequest;
use App\Modules\Admissions\Http\Resources\ApplicationResource;
use App\Modules\Admissions\Http\Resources\DocumentResource;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationDocument;
use App\Modules\Admissions\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApplicationApiController extends BaseApiController
{
    public function apply(ApplyProgramRequest $request, CreateDraftApplicationAction $action, UpdateDraftApplicationAction $updateDraft): JsonResponse
    {
        $user = $request->user();

        // The applicant can only apply with the mobile number they verified by OTP.
        if ($user->phone_number !== $request->phone && (string) $user->phone_number !== (string) $request->phone) {
            return $this->respondWithError('The phone number must be the one you verified.', ['phone' => ['Phone number does not match your verified account.']], 422);
        }

        $program = AdmissionProgram::where('is_active', true)->findOrFail($request->program_id);
        $isGuardian = $request->user()->tokenCan('guardian');

        return $this->guard(function () use ($action, $updateDraft, $program, $request, $user, $isGuardian) {
            $application = $action->execute($program, collect($request->validated())->except('guardian_relation')->all(), $user);

            // A signed-in guardian is the primary guardian, as on the website. No need to send them again.
            if ($isGuardian) {
                $updateDraft->execute($application, ['guardians' => [[
                    'relation' => $request->input('guardian_relation', 'GUARDIAN'),
                    'full_name' => $user->name,
                    'phone' => $user->phone_number,
                    'is_primary' => true,
                ]]], $user);
            }

            $application->load(['program', 'guardians', 'documents']);

            return $this->respondWithSuccess(
                new ApplicationResource($application),
                'Draft application created successfully.',
                ['application_number' => $application->application_no],
                201
            );
        });
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $application = $this->findApplication($id);
        $this->authorizeApplication($request, $application);

        $application->load(['program', 'guardians', 'documents', 'assessments', 'latestDecision', 'payments', 'statusHistory']);

        return $this->respondWithSuccess(new ApplicationResource($application), 'Application retrieved.');
    }

    public function update(string $id, UpdateApplicationRequest $request, UpdateDraftApplicationAction $action): JsonResponse
    {
        $application = $this->findApplication($id);
        $this->authorizeApplication($request, $application);

        return $this->guard(function () use ($action, $application, $request) {
            $updated = $action->execute($application, $request->validated(), $request->user());

            return $this->respondWithSuccess(new ApplicationResource($updated), 'Application updated successfully.');
        });
    }

    public function uploadDocument(string $id, UploadDocumentRequest $request, DocumentService $documents): JsonResponse
    {
        $application = $this->findApplication($id);
        $this->authorizeApplication($request, $application);

        if ($application->application_status->value !== 'DRAFT' && $application->application_status->value !== 'PENDING_APP_FEE') {
            return $this->respondWithError('Documents can no longer be changed for this application.', null, 422);
        }

        return $this->guard(function () use ($documents, $application, $request) {
            $document = $documents->store($application, $request->file('file'), DocType::from($request->doc_type), $request->user());

            return $this->respondWithSuccess(new DocumentResource($document), 'Document uploaded successfully.', [], 201);
        });
    }

    public function documents(Request $request, string $id): JsonResponse
    {
        $application = $this->findApplication($id);
        $this->authorizeApplication($request, $application);

        return $this->respondWithSuccess(
            DocumentResource::collection($application->documents()->get()),
            'Documents retrieved. Links expire after 10 minutes.'
        );
    }

    public function submit(string $id, Request $request, SubmitApplicationAction $action): JsonResponse
    {
        $application = $this->findApplication($id);
        $this->authorizeApplication($request, $application);

        return $this->guard(function () use ($action, $application, $request) {
            $submitted = $action->execute($application, $request->user());
            $submitted->load(['program', 'guardians', 'documents', 'payments']);

            return $this->respondWithSuccess(new ApplicationResource($submitted), 'Application submitted successfully.');
        });
    }

    public function withdraw(string $id, Request $request, WithdrawApplicationAction $action): JsonResponse
    {
        $application = $this->findApplication($id);
        $this->authorizeApplication($request, $application);

        return $this->guard(function () use ($action, $application, $request) {
            $withdrawn = $action->execute($application, $request->user(), 'Withdrawn by applicant.');

            return $this->respondWithSuccess(new ApplicationResource($withdrawn), 'Application withdrawn.');
        });
    }

    private function findApplication(string $id): Application
    {
        return Application::where('id', $id)->orWhere('application_no', $id)->firstOrFail();
    }
}
