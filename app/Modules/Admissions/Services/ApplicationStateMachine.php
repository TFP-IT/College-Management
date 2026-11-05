<?php

namespace App\Modules\Admissions\Services;

use App\Models\User;
use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Events\ApplicationStatusChanged;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place where an application's status changes. Transitions follow section 5 of the brief.
 */
class ApplicationStateMachine
{
    private const S = ApplicationStatus::class;

    /** @var array<string, string[]> */
    public const TRANSITIONS = [
        'DRAFT' => ['PENDING_APP_FEE', 'SUBMITTED', 'WITHDRAWN', 'EXPIRED'],
        'PENDING_APP_FEE' => ['SUBMITTED', 'WITHDRAWN', 'EXPIRED'],
        'SUBMITTED' => ['UNDER_REVIEW', 'WITHDRAWN'],
        'UNDER_REVIEW' => ['EXAM_SCHEDULED', 'ASSESSED'],
        'EXAM_SCHEDULED' => ['ASSESSED'],
        'ASSESSED' => ['APPROVED', 'WAITLISTED', 'REJECTED'],
        'WAITLISTED' => ['APPROVED', 'REJECTED'],
        'APPROVED' => ['ADMISSION_FEE_PAID', 'WITHDRAWN', 'EXPIRED'],
        'ADMISSION_FEE_PAID' => ['ENROLLED'],
        'REJECTED' => [],
        'ENROLLED' => [],
        'WITHDRAWN' => [],
        'EXPIRED' => [],
    ];

    public function __construct(private AuditService $audit) {}

    public function canTransition(Application $application, ApplicationStatus $to): bool
    {
        $current = $application->application_status->value;

        return in_array($to->value, self::TRANSITIONS[$current] ?? [], true);
    }

    /**
     * @throws ValidationException 422 when the transition is not allowed.
     */
    public function transition(
        Application $application,
        ApplicationStatus $to,
        ?User $byUser = null,
        ?string $remarks = null
    ): Application {
        return DB::transaction(function () use ($application, $to, $byUser, $remarks) {
            // Re-read under a row lock so two requests cannot both move the same application.
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $from = $locked->application_status;

            if (!in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot move this application from {$from->label()} to {$to->label()}.",
                ]);
            }

            $locked->status = $to->value;
            $locked->save();
            $application->setRawAttributes($locked->getAttributes(), true);

            ApplicationStatusHistory::create([
                'application_id' => $locked->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_id' => $byUser?->id,
                'reason' => $remarks,
                'created_at' => now(),
            ]);

            $this->audit->record(
                "status_{$from->value}_to_{$to->value}",
                $locked->id,
                ['status' => $from->value],
                ['status' => $to->value],
                $byUser
            );

            event(new ApplicationStatusChanged($locked, $from, $to, $byUser, $remarks));

            return $locked;
        });
    }
}
