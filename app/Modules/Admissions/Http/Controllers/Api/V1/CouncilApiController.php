<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Http\Requests\RecordDecisionRequest;
use App\Modules\Admissions\Http\Resources\ApplicationResource;
use App\Modules\Admissions\Http\Resources\DecisionResource;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouncilApiController extends BaseApiController
{
    /** Filters: status, program_id, search, from, to, min_score, max_score, page, per_page */
    public function index(Request $request): JsonResponse
    {
        $query = Application::with(['program', 'guardians', 'assessments', 'latestDecision'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper($request->status)))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($w) => $w->where('application_no', 'like', "%{$s}%")
                    ->orWhere('applicant_name', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%"));
            })
            ->when($request->filled('min_score') || $request->filled('max_score'), function ($q) use ($request) {
                $q->whereHas('assessments', function ($a) use ($request) {
                    if ($request->filled('min_score')) {
                        $a->where('score', '>=', (float) $request->min_score);
                    }
                    if ($request->filled('max_score')) {
                        $a->where('score', '<=', (float) $request->max_score);
                    }
                });
            });

        $perPage = min((int) ($request->per_page ?? 15), 100);
        $applications = $query->latest()->paginate($perPage);

        return $this->respondWithSuccess(
            ApplicationResource::collection($applications),
            'Applications retrieved.',
            ['current_page' => $applications->currentPage(), 'last_page' => $applications->lastPage(), 'total' => $applications->total()]
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $application = Application::with([
            'program', 'guardians', 'documents', 'assessments.evaluator',
            'decisions.decider', 'latestDecision.decider', 'payments', 'statusHistory.changedBy',
        ])->where('id', $id)->orWhere('application_no', $id)->firstOrFail();

        return $this->respondWithSuccess(new ApplicationResource($application), 'Application dossier retrieved.');
    }

    /** Dashboard counts for the pipeline. */
    public function dashboard(): JsonResponse
    {
        $count = fn (array $statuses) => Application::whereIn('status', $statuses)->count();

        return $this->respondWithSuccess([
            'total' => Application::count(),
            'pending_verification' => $count(['SUBMITTED']),
            'under_assessment' => $count(['UNDER_REVIEW', 'EXAM_SCHEDULED', 'ASSESSED']),
            'approved' => $count(['APPROVED', 'ADMISSION_FEE_PAID']),
            'waitlisted' => $count(['WAITLISTED']),
            'rejected' => $count(['REJECTED']),
            'enrolled' => $count(['ENROLLED']),
            'fees_collected' => (float) AdmissionPayment::where('payment_status', 'SUCCESS')->sum('amount'),
        ], 'Dashboard metrics.');
    }

    public function recordDecision(string $id, RecordDecisionRequest $request, RecordCouncilDecisionAction $action): JsonResponse
    {
        return $this->decide(Application::where('id', $id)->orWhere('application_no', $id)->firstOrFail(), $request, $action);
    }

    public function recordDecisionFromBody(RecordDecisionRequest $request, RecordCouncilDecisionAction $action): JsonResponse
    {
        $request->validate(['application_id' => 'required|string']);

        return $this->decide(Application::where('id', $request->application_id)->orWhere('application_no', $request->application_id)->firstOrFail(), $request, $action);
    }

    private function decide(Application $application, RecordDecisionRequest $request, RecordCouncilDecisionAction $action): JsonResponse
    {
        return $this->guard(function () use ($application, $request, $action) {
            $decision = CouncilDecision::from($request->decision);
            $record = $action->execute(
                application: $application,
                decision: $decision,
                remarks: $request->remarks ?? $request->notes,
                decidedBy: $request->user()
            );

            return $this->respondWithSuccess(
                new DecisionResource($record),
                "Council decision recorded as {$record->decision->value}."
            );
        });
    }
}
