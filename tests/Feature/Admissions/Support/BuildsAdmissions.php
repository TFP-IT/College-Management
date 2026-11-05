<?php

namespace Tests\Feature\Admissions\Support;

use App\Models\User;
use App\Modules\Admissions\Actions\InitiatePaymentAction;
use App\Modules\Admissions\Actions\ProcessPaymentCallbackAction;
use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Models\AcademicSection;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationDocument;
use App\Modules\Admissions\Payments\MockGateway;
use App\Modules\Admissions\Services\ApplicantAccountService;
use App\Modules\Admissions\Services\NumberSequenceService;
use App\Modules\Admissions\Services\PaymentSignature;
use App\Modules\Admissions\Services\QrTokenService;
use Illuminate\Support\Str;

/**
 * Shared builders for admissions tests. They go through the real services, so the rules are exercised,
 * but they skip the parts a test is not about (like a real upload).
 */
trait BuildsAdmissions
{
    protected function program(string $code = 'PG-2026'): AdmissionProgram
    {
        return AdmissionProgram::where('code', $code)->firstOrFail();
    }

    /** A section of the programme's class that matches its group and year. */
    protected function sectionFor(AdmissionProgram $program, string $name = 'A'): AcademicSection
    {
        return AcademicSection::where('class_id', $program->class_id)
            ->where('academic_year_id', $program->academic_year_id)
            ->when($program->group_id, fn ($q) => $q->where('group_id', $program->group_id))
            ->where('name', $name)
            ->firstOrFail();
    }

    protected function staff(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** A date of birth that makes the child the right age for the programme (age is measured at the programme's opening). */
    protected function dobForProgram(AdmissionProgram $program, int $age): string
    {
        $reference = $program->application_opens_at ?? now();

        return $reference->copy()->subYears($age)->toDateString();
    }

    /** Creates an application directly in a given status (for tests of later stages). */
    protected function makeApplication(array $overrides = [], string $programCode = 'PG-2026', int $age = 4): Application
    {
        $program = $this->program($programCode);

        return Application::create(array_merge([
            'program_id' => $program->id,
            'application_no' => app(NumberSequenceService::class)->generateApplicationNumber(),
            'applicant_name' => 'Test Child ' . Str::random(4),
            'mobile' => '01' . random_int(300000000, 999999999),
            'dob' => $this->dobForProgram($program, $age),
            'gender' => 'MALE',
            'current_address' => 'Test Road, Savar',
            'status' => 'DRAFT',
        ], $overrides));
    }

    protected function addGuardian(Application $application, string $phone = '01711111111', bool $primary = true): void
    {
        $application->guardians()->create([
            'name' => 'Guardian ' . Str::random(3),
            'relation' => 'FATHER',
            'mobile' => $phone,
            'nid_passport' => '19801234567890',
            'is_primary' => $primary,
        ]);
    }

    /** @param string[] $types */
    protected function addDocuments(Application $application, array $types = ['PHOTO', 'BIRTH_CERT'], DocStatus $status = DocStatus::VERIFIED): void
    {
        foreach ($types as $type) {
            ApplicationDocument::create([
                'application_id' => $application->id,
                'doc_type' => $type,
                'object_key' => "admissions/{$application->id}/" . Str::uuid() . '.png',
                'file_name' => strtolower($type) . '.png',
                'mime_type' => 'image/png',
                'size_bytes' => 1000,
                'checksum_sha256' => hash('sha256', $type),
                'status' => $status->value,
            ]);
        }
    }

    /** Signed callback for the mock gateway. */
    protected function signedCallback(AdmissionPayment $payment, string $status = 'SUCCESS', ?string $amount = null): array
    {
        $amount ??= number_format((float) $payment->amount, 2, '.', '');

        return [
            'transaction_ref' => $payment->transaction_ref,
            'status' => $status,
            'amount' => $amount,
            'currency' => 'BDT',
            'signature' => PaymentSignature::sign($payment->transaction_ref, $status, $amount),
        ];
    }

    /** Initiates and pays a fee through the real actions. */
    protected function payFee(Application $application, PaymentType $type): AdmissionPayment
    {
        $initiated = app(InitiatePaymentAction::class)->execute($application, $type, 'mock');
        app(ProcessPaymentCallbackAction::class)->execute($initiated['payment'], $this->signedCallback($initiated['payment']));

        return $initiated['payment']->fresh();
    }

    protected function issueQr(Application $application): void
    {
        app(QrTokenService::class)->generateToken($application->fresh());
    }

    protected function applicantFor(string $mobile): User
    {
        return app(ApplicantAccountService::class)->forMobile($mobile, 'Applicant');
    }
}
