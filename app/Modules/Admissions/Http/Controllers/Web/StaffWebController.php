<?php

namespace App\Modules\Admissions\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Admissions\Actions\EnrollStudentAction;
use App\Modules\Admissions\Actions\RecordAssessmentScoreAction;
use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Actions\ScheduleAssessmentAction;
use App\Modules\Admissions\Actions\VerifyDocumentAction;
use App\Modules\Admissions\Enums\AssessmentType;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Models\AcademicSection;
use App\Modules\Admissions\Models\AdmissionAssessment;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationDocument;
use Illuminate\Http\Request;

class StaffWebController extends Controller
{
    /**
     * Admin Admissions Dashboard.
     */
    public function dashboard(Request $request)
    {
        $programs = AdmissionProgram::all();

        $query = Application::with(['program', 'guardians', 'assessments', 'latestDecision']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('application_no', 'like', "%{$s}%")
                  ->orWhere('applicant_name', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $applications = $query->latest()->paginate(20);

        // Metrics
        $metrics = [
            'total' => Application::count(),
            'pending_review' => Application::whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'EXAM_SCHEDULED', 'ASSESSED'])->count(),
            'approved' => Application::whereIn('status', ['APPROVED', 'ADMISSION_FEE_PAID'])->count(),
            'enrolled' => Application::where('status', 'ENROLLED')->count(),
            'fees_collected' => AdmissionPayment::where('payment_status', 'SUCCESS')->sum('amount'),
        ];

        return view('admissions.admin.dashboard', compact('applications', 'programs', 'metrics'));
    }

    /**
     * Split-screen Application Evaluation Dossier.
     */
    public function show(string $id)
    {
        $application = Application::with([
            'program.academicYear',
            'program.academicClass',
            'program.academicGroup',
            'guardians',
            'documents',
            'assessments.evaluator',
            'decisions.decider',
            'latestDecision.decider',
            'payments',
            'statusHistory.changedBy',
        ])->findOrFail($id);

        $availableSections = AcademicSection::where('class_id', $application->program->class_id)
            ->where('academic_year_id', $application->program->academic_year_id)
            ->when($application->program->group_id, fn($q) => $q->where('group_id', $application->program->group_id))
            ->get();

        return view('admissions.admin.show', compact('application', 'availableSections'));
    }

    /**
     * Document review action.
     */
    public function verifyDocument(
        Request $request,
        string $applicationId,
        string $documentId,
        VerifyDocumentAction $action
    ) {
        $document = ApplicationDocument::where('application_id', $applicationId)
            ->where('id', $documentId)
            ->firstOrFail();

        $status = DocStatus::from($request->input('status', 'VERIFIED'));
        $action->execute(
            document: $document,
            status: $status,
            reviewNotes: $request->input('review_notes'),
            reviewer: auth()->user()
        );

        return back()->with('success', "Document status updated to {$status->value}.");
    }

    /**
     * Schedule exam/assessment.
     */
    public function scheduleAssessment(
        Request $request,
        string $applicationId,
        ScheduleAssessmentAction $action
    ) {
        $application = Application::findOrFail($applicationId);

        $request->validate([
            'assessment_type' => 'required|in:WRITTEN,VIVA,INTERVIEW',
            'scheduled_at' => 'required|date',
            'venue' => 'nullable|string|max:255',
        ]);

        $action->execute(
            application: $application,
            type: AssessmentType::from($request->assessment_type),
            scheduledAt: $request->scheduled_at,
            venue: $request->venue,
            scheduledBy: auth()->user()
        );

        return back()->with('success', 'Assessment exam scheduled successfully.');
    }

    /**
     * Record marks for assessment.
     */
    public function recordScore(
        Request $request,
        string $applicationId,
        string $assessmentId,
        RecordAssessmentScoreAction $action
    ) {
        $assessment = AdmissionAssessment::where('application_id', $applicationId)
            ->where('id', $assessmentId)
            ->firstOrFail();

        $request->validate([
            'obtained_marks' => 'required|numeric|min:0|max:100',
            'comments' => 'nullable|string|max:1000',
        ]);

        $action->execute(
            assessment: $assessment,
            obtainedMarks: (float) $request->obtained_marks,
            comments: $request->comments,
            evaluator: auth()->user()
        );

        return back()->with('success', 'Assessment marks recorded successfully.');
    }

    /**
     * Record council admission decision.
     */
    public function recordDecision(
        Request $request,
        string $applicationId,
        RecordCouncilDecisionAction $action
    ) {
        $application = Application::findOrFail($applicationId);

        $request->validate([
            'decision' => 'required|in:APPROVED,WAITLISTED,REJECTED',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $decision = CouncilDecision::from($request->decision);
        $action->execute(
            application: $application,
            decision: $decision,
            remarks: $request->remarks,
            decidedBy: auth()->user()
        );

        return back()->with('success', "Council decision recorded as {$decision->value}.");
    }

    /**
     * Final enrollment & Student ID provisioning.
     */
    public function enroll(
        Request $request,
        string $applicationId,
        EnrollStudentAction $action
    ) {
        $application = Application::findOrFail($applicationId);

        $request->validate([
            'section_id' => 'required|uuid|exists:academic_sections,id',
            'roll_no' => 'nullable|string|max:20',
        ]);

        $section = AcademicSection::findOrFail($request->section_id);

        try {
            $studentProfile = $action->execute(
                application: $application,
                section: $section,
                enroller: auth()->user(),
                rollNo: $request->roll_no
            );

            return back()->with('success', "Candidate successfully enrolled! Permanent Student ID: {$studentProfile->student_number}");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
