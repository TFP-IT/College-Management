<?php

namespace Tests\Feature\Cms;

use App\Models\User;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Cms\Models\CmsPage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdmissionContentCmsTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        return User::where('email', 'admin@mcc.edu.bd')->firstOrFail();
    }

    private function programmeForm(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Playgroup Admission 2026–27',
            'description' => 'Two-year play-based programme for young children.',
            'fee_application' => 500,
            'fee_admission' => 12500,
            'seats' => 80,
            'application_opens_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'application_deadline' => now()->addMonth()->format('Y-m-d\TH:i'),
            'is_active' => '1',
        ], $overrides);
    }

    public function test_the_cms_lists_admission_programmes_for_a_super_admin(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin/cms')->assertOk()->assertSee('Admission programmes');
        $this->actingAs($this->superAdmin())->get('/admin/cms/admission-programs')->assertOk()->assertSee('Playgroup Admission');
    }

    public function test_principal_cannot_edit_programmes(): void
    {
        $principal = User::where('email', 'principal@mcc.edu.bd')->firstOrFail();

        $this->actingAs($principal)->get('/admin/cms/admission-programs')->assertStatus(403);
        $this->actingAs($principal)->get('/admin/cms')->assertOk()->assertDontSee('Admission programmes');
    }

    public function test_an_edit_to_a_programme_shows_on_the_programmes_page(): void
    {
        $program = AdmissionProgram::where('code', 'PG-2026')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->put(route('cms.admin.update', ['admission-programs', $program->id]), $this->programmeForm())
            ->assertRedirect();

        $this->assertEquals(12500, (float) $program->fresh()->fee_admission);
        $this->get('/programs')
            ->assertOk()
            ->assertSee('Two-year play-based programme')
            ->assertSee('12,500.00');
    }

    public function test_switching_a_programme_off_hides_it_from_the_public_page(): void
    {
        $program = AdmissionProgram::where('code', 'PG-2026')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->put(route('cms.admin.update', ['admission-programs', $program->id]), $this->programmeForm(['is_active' => '0']))
            ->assertRedirect();

        $this->assertFalse($program->fresh()->is_active);
        $this->get('/programs')->assertDontSee('Two-year play-based programme');
    }

    public function test_programmes_cannot_be_created_or_deleted_from_the_cms(): void
    {
        $admin = $this->superAdmin();
        $program = AdmissionProgram::where('code', 'PG-2026')->firstOrFail();

        $this->actingAs($admin)->get('/admin/cms/admission-programs/create')->assertStatus(403);
        $this->actingAs($admin)->post('/admin/cms/admission-programs', $this->programmeForm(['title' => 'New one']))->assertStatus(403);
        $this->actingAs($admin)->delete(route('cms.admin.destroy', ['admission-programs', $program->id]))->assertStatus(403);

        $this->assertNotNull(AdmissionProgram::where('code', 'PG-2026')->first());
    }

    public function test_deadline_before_opening_is_refused(): void
    {
        $program = AdmissionProgram::where('code', 'PG-2026')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->put(route('cms.admin.update', ['admission-programs', $program->id]), $this->programmeForm([
                'application_opens_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'application_deadline' => now()->addDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('application_deadline');
    }

    public function test_negative_fees_are_refused(): void
    {
        $program = AdmissionProgram::where('code', 'PG-2026')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->put(route('cms.admin.update', ['admission-programs', $program->id]), $this->programmeForm(['fee_admission' => -1]))
            ->assertSessionHasErrors('fee_admission');
    }

    public function test_the_admission_page_uses_the_cms_text_when_published(): void
    {
        CmsPage::create(['slug' => 'admission', 'title' => 'Admission', 'body' => '<p>Bring the original birth certificate.</p>', 'is_published' => true]);

        $this->get('/admission')->assertOk()->assertSee('Bring the original birth certificate.');
    }
}
