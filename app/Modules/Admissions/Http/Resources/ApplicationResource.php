<?php

namespace App\Modules\Admissions\Http\Resources;

use App\Modules\Admissions\Services\QrTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * The admission pass is shown only after approval, and only to the applicant or to staff.
     */
    private function qrForViewer(Request $request): ?array
    {
        $viewer = $request->user();
        $isOwner = $viewer && (string) $this->applicant_user_id === (string) $viewer->id;
        $isStaff = $viewer && $viewer->can('admission.application.view');

        if (!($isOwner || $isStaff) || empty($this->qr_token)) {
            return null;
        }

        if (!in_array($this->application_status?->value, ['APPROVED', 'ADMISSION_FEE_PAID', 'ENROLLED'], true)) {
            return null;
        }

        return [
            'token' => $this->qr_token,
            'verification_url' => app(QrTokenService::class)->getVerificationUrl($this->resource),
            'expires_at' => $this->qr_expires_at?->toIso8601String(),
            'used' => $this->qr_used_at !== null,
        ];
    }

    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'application_number' => $this->application_number,
            'program' => new ProgramResource($this->whenLoaded('program')),
            'status' => [
                'value' => $this->application_status?->value,
                'label' => $this->application_status?->label(),
                'badge_class' => $this->application_status?->badgeClass(),
            ],
            'candidate' => [
                'name' => $this->candidate_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'dob' => $this->candidate_dob?->format('Y-m-d'),
                'gender' => $this->gender,
                'blood_group' => $this->blood_group,
                'current_address' => $this->current_address,
                'permanent_address' => $this->permanent_address,
                'previous_school' => $this->previous_school,
                'previous_gpa' => $this->previous_gpa !== null ? (float) $this->previous_gpa : null,
                'academic_history' => $this->academic_history ?? [],
            ],
            'guardians' => $this->guardians->map(fn($g) => [
                'id' => $g->id,
                'relation' => $g->relation,
                'full_name' => $g->full_name,
                'phone' => $g->phone,
                'email' => $g->email,
                'occupation' => $g->occupation,
                'is_primary' => (bool) $g->is_primary,
            ]),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'assessments' => AssessmentResource::collection($this->whenLoaded('assessments')),
            'decisions' => DecisionResource::collection($this->whenLoaded('decisions')),
            'latest_decision' => new DecisionResource($this->whenLoaded('latestDecision')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'qr' => $this->qrForViewer($request),
            'admission_student_id' => $this->admission_student_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
