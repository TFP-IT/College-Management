<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admissions\Actions\RecordCouncilDecisionAction;
use App\Modules\Admissions\Enums\CouncilDecision;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Cms\Models\CmsNotice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

/** Every page a visitor, applicant or staff member can open must render (no 500s, no broken templates). */
class SmokePagesTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    public function test_public_website_pages_render(): void
    {
        foreach (['/', '/about', '/notice', '/gallery', '/governing-body', '/teacher-information', '/staff-information', '/admission', '/programs', '/contact', '/academic-calendar', '/chairmans-profile', '/message-of-chairman', '/message-of-principal', '/admissions', '/admissions/track'] as $url) {
            $this->get($url)->assertStatus(200, "Page {$url} failed");
        }
    }

    public function test_applicant_pages_render_for_an_owner_session(): void
    {
        $application = $this->makeApplication(['status' => 'APPROVED']);
        $this->issueQr($application);

        $this->withSession(['admissions.track_application_id' => $application->id])
            ->get("/admissions/applications/{$application->id}")
            ->assertOk()
            ->assertSee('Admission pass', false)
            ->assertSee('<svg', false);

        $this->get("/admissions/qr/{$application->fresh()->qr_token}")->assertOk()->assertSee('Valid admission pass');
    }

    public function test_applicant_page_renders_for_a_draft_without_a_pass(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);

        $this->withSession(['admissions.track_application_id' => $application->id])
            ->get("/admissions/applications/{$application->id}")
            ->assertOk()
            ->assertDontSee('Official Digital Admission QR Pass');
    }

    public function test_staff_pages_render(): void
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW']);
        $officer = $this->staff('officer@mcc.edu.bd');

        $this->actingAs($officer)->get('/admin/admissions')->assertOk();
        $this->actingAs($officer)->get("/admin/admissions/applications/{$application->id}")->assertOk();
    }

    public function test_cms_admin_pages_render_for_the_principal(): void
    {
        $principal = $this->staff('principal@mcc.edu.bd');
        $this->actingAs($principal);

        $this->get('/admin/cms')->assertOk();
        foreach (['notices', 'gallery', 'staff', 'pages'] as $key) {
            $this->get("/admin/cms/{$key}")->assertOk();
            $this->get("/admin/cms/{$key}/create")->assertOk();
        }

        $notice = CmsNotice::first();
        $this->get("/admin/cms/notices/{$notice->id}/edit")->assertOk();
    }

    public function test_the_principal_has_cms_and_admission_reporting_but_not_enrolment(): void
    {
        $principal = $this->staff('principal@mcc.edu.bd');

        $this->assertTrue($principal->can('cms.manage'));
        $this->assertTrue($principal->can('admission.report.view'));
        $this->assertFalse($principal->can('admission.enroll'));
    }

    public function test_admin_pages_refuse_a_logged_in_user_without_permission(): void
    {
        $teacher = User::where('email', 'teacher@gmail.com')->firstOrFail();

        $this->actingAs($teacher)->get('/admin/admissions')->assertStatus(403);
        $this->actingAs($teacher)->get('/admin/cms')->assertStatus(403);
    }
}
