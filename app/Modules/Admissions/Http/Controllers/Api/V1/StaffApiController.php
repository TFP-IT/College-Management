<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Actions\EnrollStudentAction;
use App\Modules\Admissions\Actions\RecordAssessmentScoreAction;
use App\Modules\Admissions\Actions\ScheduleAssessmentAction;
use App\Modules\Admissions\Actions\VerifyDocumentAction;
use App\Modules\Admissions\Enums\AssessmentType;
use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Http\Requests\EnrollApplicantRequest;
use App\Modules\Admissions\Http\Requests\RecordScoreRequest;
use App\Modules\Admissions\Http\Requests\ScheduleAssessmentRequest;
use App\Modules\Admissions\Http\Resources\AssessmentResource;
use App\Modules\Admissions\Http\Resources\DocumentResource;
use App\Modules\Admissions\Models\AcademicSection;
use App\Modules\Admissions\Models\AdmissionAssessment;
use App\Modules\Admissions\Models\AcademicClass;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationDocument;
use App\Modules\Admissions\Services\QrTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StaffApiController extends BaseApiController
{
    public function verifyDocument(string $id, string $docId, Request $request, VerifyDocumentAction $action): JsonResponse
    {
        $document = ApplicationDocument::where('application_id', $id)->where('id', $docId)->firstOrFail();

        return $this->verify($document, $request, $action);
    }

    public function verifyDocumentById(string $docId, Request $request, VerifyDocumentAction $action): JsonResponse
    {
        $request->validate(['application_id' => 'required|string']);
        $document = ApplicationDocument::where('application_id', $request->application_id)->where('id', $docId)->firstOrFail();

        return $this->verify($document, $request, $action);
    }

    private function verify(ApplicationDocument $document, Request $request, VerifyDocumentAction $action): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:VERIFIED,REJECTED',
            'review_notes' => 'nullable|string|max:500',
        ]);

        return $this->guard(function () use ($document, $request, $action) {
            $status = DocStatus::from($request->status);
            $updated = $action->execute($document, $status, $request->review_notes, $request->user());

            return $this->respondWithSuccess(new DocumentResource($updated), "Document marked as {$status->value}.");
        });
    }

    public function scheduleAssessment(string $id, ScheduleAssessmentRequest $request, ScheduleAssessmentAction $action): JsonResponse
    {
        return $this->schedule(Application::where('id', $id)->orWhere('application_no', $id)->firstOrFail(), $request, $action);
    }

    public function scheduleAssessmentFromBody(ScheduleAssessmentRequest $request, ScheduleAssessmentAction $action): JsonResponse
    {
        $request->validate(['application_id' => 'required|string']);

        return $this->schedule(Application::where('id', $request->application_id)->orWhere('application_no', $request->application_id)->firstOrFail(), $request, $action);
    }

    private function schedule(Application $application, ScheduleAssessmentRequest $request, ScheduleAssessmentAction $action): JsonResponse
    {
        return $this->guard(function () use ($application, $request, $action) {
            $assessment = $action->execute(
                application: $application,
                type: AssessmentType::from($request->assessment_type),
                scheduledAt: $request->scheduled_at,
                venue: $request->venue,
                scheduledBy: $request->user()
            );

            return $this->respondWithSuccess(new AssessmentResource($assessment), 'Assessment scheduled.', [], 201);
        });
    }

    public function recordScore(string $id, string $assessmentId, RecordScoreRequest $request, RecordAssessmentScoreAction $action): JsonResponse
    {
        $assessment = AdmissionAssessment::where('application_id', $id)->where('id', $assessmentId)->firstOrFail();

        return $this->score($assessment, $request, $action);
    }

    public function recordScoreById(string $assessmentId, RecordScoreRequest $request, RecordAssessmentScoreAction $action): JsonResponse
    {
        $request->validate(['application_id' => 'required|string']);
        $assessment = AdmissionAssessment::where('application_id', $request->application_id)->where('id', $assessmentId)->firstOrFail();

        return $this->score($assessment, $request, $action);
    }

    private function score(AdmissionAssessment $assessment, RecordScoreRequest $request, RecordAssessmentScoreAction $action): JsonResponse
    {
        return $this->guard(function () use ($assessment, $request, $action) {
            $recorded = $action->execute(
                assessment: $assessment,
                obtainedMarks: (float) $request->obtained_marks,
                comments: $request->comments,
                evaluator: $request->user()
            );

            return $this->respondWithSuccess(new AssessmentResource($recorded), 'Assessment marks recorded.');
        });
    }

    public function enroll(string $id, EnrollApplicantRequest $request, EnrollStudentAction $action): JsonResponse
    {
        return $this->doEnroll(Application::where('id', $id)->orWhere('application_no', $id)->firstOrFail(), $request, $action);
    }

    public function enrollFromBody(EnrollApplicantRequest $request, EnrollStudentAction $action): JsonResponse
    {
        $request->validate(['application_id' => 'required|string']);

        return $this->doEnroll(Application::where('id', $request->application_id)->orWhere('application_no', $request->application_id)->firstOrFail(), $request, $action);
    }

    private function doEnroll(Application $application, EnrollApplicantRequest $request, EnrollStudentAction $action): JsonResponse
    {
        return $this->guard(function () use ($application, $request, $action) {
            $section = AcademicSection::with('academicClass')->findOrFail($request->section_id);
            $profile = $action->execute($application, $section, $request->user(), $request->roll_no);

            return $this->respondWithSuccess([
                'student_id' => $profile->student_number,
                'student_profile_id' => $profile->id,
                'section' => $section->name,
                'class' => $section->academicClass?->name,
                'application_number' => $application->application_no,
            ], "Candidate enrolled. Student ID: {$profile->student_number}");
        });
    }

    /** Options for allocation, with remaining seats. */
    public function classes(): JsonResponse
    {
        return $this->respondWithSuccess(
            AcademicClass::orderBy('sort_order')->get(['id', 'code', 'name', 'section_type']),
            'Classes retrieved.'
        );
    }

    public function sections(Request $request): JsonResponse
    {
        $request->validate(['program_id' => 'required|uuid']);
        $program = \App\Modules\Admissions\Models\AdmissionProgram::findOrFail($request->program_id);

        $sections = AcademicSection::where('class_id', $program->class_id)
            ->where('academic_year_id', $program->academic_year_id)
            ->when($program->group_id, fn ($q) => $q->where('group_id', $program->group_id))
            ->get()
            ->map(fn (AcademicSection $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'capacity' => $s->capacity,
                'available_seats' => $s->availableSeatsCount(),
            ]);

        return $this->respondWithSuccess($sections, 'Sections retrieved.');
    }

    public function verifyQr(Request $request, QrTokenService $qr): JsonResponse
    {
        $request->validate(['token' => 'required|string|max:1000']);

        try {
            $decoded = $qr->verifyToken($request->token);
        } catch (InvalidArgumentException $e) {
            return $this->respondWithError($e->getMessage(), null, 422);
        }

        $application = Application::with('program')->find($decoded['application_id']);
        if (!$application) {
            return $this->respondWithError('QR token does not match an application.', null, 422);
        }

        return $this->respondWithSuccess([
            'valid' => $application->qrIsValid(),
            'application_number' => $application->application_no,
            'status' => $application->application_status->value,
            'used' => $application->qr_used_at !== null,
            'expires_at' => $application->qr_expires_at?->toIso8601String(),
        ], $application->qrIsValid() ? 'QR is valid.' : 'QR is not valid for entry.');
    }
}
