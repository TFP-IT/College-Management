<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Services\PaymentSignature;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

/**
 * The whole journey over HTTP, the way the mobile app and the staff desk use it.
 * Only the gateway is simulated (with real signatures).
 */
class HttpJourneyTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    public function test_applicant_to_enrolment_over_the_api(): void
    {
        Storage::fake('local');
        $program = $this->program('PG-2026');
        $phone = '01755500002';
        $dob = $this->dobForProgram($program, 4);

        // 1. Verify the mobile number and get an applicant token.
        $code = $this->postJson('/api/v1/admissions/applicants/otp/request', ['phone' => $phone])->json('data.debug_otp');
        $applicantToken = $this->postJson('/api/v1/admissions/applicants/otp/verify', ['phone' => $phone, 'code' => $code])->json('data.token');
        $this->assertNotEmpty($applicantToken);

        // 2. Start the draft.
        $appId = $this->withToken($applicantToken)->postJson('/api/v1/admissions/apply', [
            'program_id' => $program->id, 'candidate_name' => 'Journey Child', 'phone' => $phone,
            'candidate_dob' => $dob, 'gender' => 'FEMALE', 'current_address' => 'Savar, Dhaka',
        ])->assertStatus(201)->json('data.id');

        // 3. Guardian details.
        $this->withToken($applicantToken)->putJson("/api/v1/admissions/applications/{$appId}", [
            'guardians' => [['relation' => 'FATHER', 'full_name' => 'Journey Father', 'phone' => '01766600002', 'is_primary' => true]],
        ])->assertOk();

        // 4. Documents.
        $photoId = $this->withToken($applicantToken)->postJson("/api/v1/admissions/applications/{$appId}/documents", [
            'doc_type' => 'PHOTO', 'file' => UploadedFile::fake()->image('photo.png'),
        ])->assertStatus(201)->json('data.id');
        $this->withToken($applicantToken)->postJson("/api/v1/admissions/applications/{$appId}/documents", [
            'doc_type' => 'BIRTH_CERT', 'file' => UploadedFile::fake()->image('birth.png'),
        ])->assertStatus(201);

        // 5. Submit, then pay the application fee.
        $this->withToken($applicantToken)->postJson("/api/v1/admissions/applications/{$appId}/submit")
            ->assertOk()->assertJsonPath('data.status.value', 'PENDING_APP_FEE');

        $appPayment = $this->withToken($applicantToken)->postJson("/api/v1/admissions/applications/{$appId}/pay", [
            'payment_type' => 'APPLICATION_FEE', 'provider' => 'mock',
        ])->assertStatus(201)->json('data.payment');
        $this->assertEquals(500, $appPayment['amount']);

        $this->signedCallbackOverHttp($appPayment['transaction_ref'], 'SUCCESS', '500.00')->assertOk();
        $this->withToken($applicantToken)->getJson("/api/v1/admissions/applications/{$appId}/status")
            ->assertJsonPath('data.status.value', 'SUBMITTED');

        // 6. Staff: verify documents, then council approves.
        $officer = $this->staffToken('officer@mcc.edu.bd', 'Officer@123');
        $council = $this->staffToken('council@mcc.edu.bd', 'Council@123');

        $this->withToken($officer)->postJson("/api/v1/admissions/officer/applications/{$appId}/verify-document/{$photoId}", ['status' => 'VERIFIED'])
            ->assertOk();
        $birthId = \App\Modules\Admissions\Models\ApplicationDocument::where('application_id', $appId)->where('doc_type', 'BIRTH_CERT')->value('id');
        $this->withToken($officer)->postJson("/api/v1/admissions/officer/applications/{$appId}/verify-document/{$birthId}", ['status' => 'VERIFIED'])
            ->assertOk();

        $this->withToken($council)->postJson("/api/v1/admissions/council/applications/{$appId}/decision", ['decision' => 'APPROVED', 'remarks' => 'Good'])
            ->assertOk();

        // The pass is now visible to the applicant.
        $qr = $this->withToken($applicantToken)->getJson("/api/v1/admissions/applications/{$appId}/status")->json('data.qr');
        $this->assertNotEmpty($qr['token']);

        // 7. Admission fee, then enrolment by a different staff member.
        $admissionPayment = $this->withToken($applicantToken)->postJson("/api/v1/admissions/applications/{$appId}/pay", [
            'payment_type' => 'ADMISSION_FEE', 'provider' => 'mock',
        ])->assertStatus(201)->json('data.payment');
        $this->assertEquals(12000, $admissionPayment['amount']);
        $this->signedCallbackOverHttp($admissionPayment['transaction_ref'], 'SUCCESS', '12000.00')->assertOk();

        $sectionId = \App\Modules\Admissions\Models\AcademicSection::where('class_id', $program->class_id)->where('name', 'A')->value('id');

        // Council members do not hold the enrolment permission at all (403). The decider-cannot-enrol rule is tested separately.
        $this->withToken($council)->postJson("/api/v1/admissions/officer/applications/{$appId}/enroll", ['section_id' => $sectionId])
            ->assertStatus(403);

        $enrol = $this->withToken($officer)->postJson("/api/v1/admissions/officer/applications/{$appId}/enroll", ['section_id' => $sectionId, 'roll_no' => '12'])
            ->assertOk()
            ->assertJsonPath('data.section', 'A');
        $studentId = $enrol->json('data.student_id');
        $this->assertStringStartsWith('MCC26S', $studentId);

        // 8. Enrolling again returns the same student and creates nothing new.
        $this->withToken($officer)->postJson("/api/v1/admissions/officer/applications/{$appId}/enroll", ['section_id' => $sectionId])
            ->assertOk()->assertJsonPath('data.student_id', $studentId);
    }

    private function staffToken(string $email, string $password): string
    {
        return $this->postJson('/api/v1/admissions/auth/login', ['email' => $email, 'password' => $password])
            ->assertOk()->json('data.token');
    }

    private function signedCallbackOverHttp(string $ref, string $status, string $amount)
    {
        return $this->postJson('/api/v1/admissions/payments/callback', [
            'transaction_ref' => $ref,
            'status' => $status,
            'amount' => $amount,
            'currency' => 'BDT',
            'signature' => PaymentSignature::sign($ref, $status, $amount),
        ]);
    }
}
