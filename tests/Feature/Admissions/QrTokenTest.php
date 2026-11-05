<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\QrTokenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class QrTokenTest extends TestCase
{
    use DatabaseTransactions;

    protected QrTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QrTokenService::class);
    }

    public function test_generates_and_verifies_valid_qr_token(): void
    {
        $program = AdmissionProgram::first();
        $application = Application::create([
            'program_id' => $program->id,
            'application_no' => 'APP-2026-TEST01',
            'applicant_name' => 'John Doe',
            'mobile' => '01700000001',
            'dob' => '2010-01-01',
            'gender' => 'MALE',
        ]);

        $token = $this->service->generateToken($application);
        $this->assertNotEmpty($token);

        $decoded = $this->service->verifyToken($token);
        $this->assertEquals($application->id, $decoded['application_id']);
        $this->assertEquals('APP-2026-TEST01', $decoded['application_number']);
    }

    public function test_rejects_tampered_qr_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->verifyToken('tampered-invalid-token-signature');
    }

    public function test_rejects_expired_qr_token(): void
    {
        $program = AdmissionProgram::first();
        $application = Application::create([
            'program_id' => $program->id,
            'application_no' => 'APP-2026-TEST02',
            'applicant_name' => 'Expired User',
            'mobile' => '01700000002',
            'dob' => '2010-01-01',
            'gender' => 'FEMALE',
        ]);

        // Generate token with expired date
        $token = $this->service->generateToken($application, now()->subDay());

        $this->expectException(InvalidArgumentException::class);
        $this->service->verifyToken($token);
    }
}
