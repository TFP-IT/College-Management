<?php

namespace Tests\Feature\Cms;

use App\Models\User;
use App\Modules\Cms\Models\CmsGalleryItem;
use App\Modules\Cms\Models\CmsNotice;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsStaffMember;
use App\Modules\Cms\Support\Sanitizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use DatabaseTransactions;

    private function principal(): User
    {
        return User::where('email', 'principal@mcc.edu.bd')->firstOrFail();
    }

    public function test_public_pages_show_the_content_that_was_hard_coded_before(): void
    {
        $this->get('/notice')->assertOk()->assertSee('HSC Admission 2026 is now open');
        $this->get('/governing-body')->assertOk()->assertSee('Dr. Kamrul Ahsan');
        $this->get('/teacher-information')->assertOk()->assertSee('Burhan Uddin');
    }

    public function test_gallery_shows_an_empty_state_when_nothing_is_published(): void
    {
        CmsGalleryItem::query()->delete();

        $this->get('/gallery')->assertOk()->assertSee('Gallery updating soon');
    }

    public function test_unpublished_expired_and_future_notices_are_hidden(): void
    {
        CmsNotice::create(['title' => 'Draft notice', 'body' => 'x', 'is_published' => false]);
        CmsNotice::create(['title' => 'Expired notice', 'body' => 'x', 'is_published' => true, 'expires_on' => now()->subDay()]);
        CmsNotice::create(['title' => 'Future notice', 'body' => 'x', 'is_published' => true, 'publish_on' => now()->addWeek()]);

        $this->get('/notice')->assertDontSee('Draft notice')->assertDontSee('Expired notice')->assertDontSee('Future notice');
    }

    public function test_admin_pages_need_login_and_the_cms_permission(): void
    {
        $this->get('/admin/cms')->assertRedirect();

        $council = User::where('email', 'council@mcc.edu.bd')->firstOrFail();
        $this->actingAs($council)->get('/admin/cms')->assertStatus(403);

        $this->actingAs($this->principal())->get('/admin/cms')->assertOk()->assertSee('Website content');
    }

    public function test_principal_publishes_a_notice_that_appears_on_the_site(): void
    {
        $this->actingAs($this->principal())->post(route('cms.admin.store', 'notices'), [
            'title' => 'Campus closed on Friday', 'body' => 'The campus will be closed this Friday.', 'is_published' => '1',
        ])->assertRedirect(route('cms.admin.list', 'notices'));

        $this->get('/notice')->assertSee('Campus closed on Friday');
    }

    public function test_editable_page_replaces_the_built_in_text_and_strips_scripts(): void
    {
        $this->actingAs($this->principal())->post(route('cms.admin.store', 'pages'), [
            'title' => 'About Us', 'slug' => 'about', 'is_published' => '1',
            'body' => '<p onclick="steal()">Hello <strong>world</strong></p><script>alert(1)</script><a href="javascript:bad()">x</a>',
        ])->assertRedirect();

        $this->get('/about')
            ->assertOk()
            ->assertSee('Hello', false)
            ->assertSee('<strong>world</strong>', false)
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('javascript:', false);
    }

    public function test_sanitizer_keeps_safe_formatting_only(): void
    {
        $clean = Sanitizer::html('<h2>Title</h2><script>x()</script><img src=x onerror=alert(1)><a href="https://mcc.edu.bd" onclick="x()">ok</a>');

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('href="https://mcc.edu.bd"', $clean);
    }

    public function test_gallery_upload_is_stored_on_the_public_disk_and_shown(): void
    {
        Storage::fake('public');

        $this->actingAs($this->principal())->post(route('cms.admin.store', 'gallery'), [
            'title' => 'Annual sports day', 'category' => 'Sports', 'sort_order' => 1, 'is_published' => '1',
            'image' => UploadedFile::fake()->image('sports.jpg'),
        ])->assertRedirect();

        $item = CmsGalleryItem::where('title', 'Annual sports day')->firstOrFail();
        Storage::disk('public')->assertExists($item->image_path);
        $this->get('/gallery')->assertSee('Annual sports day');
    }

    public function test_non_image_upload_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->principal())->post(route('cms.admin.store', 'gallery'), [
            'title' => 'Not an image', 'image' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('cms_gallery_items', ['title' => 'Not an image']);
    }

    public function test_staff_member_can_be_added_and_appears_in_the_right_section(): void
    {
        $this->actingAs($this->principal())->post(route('cms.admin.store', 'staff'), [
            'name' => 'New Teacher', 'designation' => 'Lecturer', 'group' => 'TEACHER', 'sort_order' => 1, 'is_published' => '1',
        ])->assertRedirect();

        $this->get('/teacher-information')->assertSee('New Teacher');
        $this->get('/staff-information')->assertDontSee('New Teacher');
    }

    public function test_public_api_returns_only_published_live_content(): void
    {
        CmsNotice::create(['title' => 'Hidden', 'body' => 'x', 'is_published' => false]);

        $this->getJson('/api/v1/cms/notices')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['title' => 'Hidden']);

        $this->getJson('/api/v1/cms/staff?group=GOVERNING_BODY')->assertOk()->assertJsonPath('data.0.group', 'GOVERNING_BODY');
        $this->getJson('/api/v1/cms/pages/does-not-exist')->assertStatus(404);
    }

    public function test_api_page_body_is_sanitised(): void
    {
        CmsPage::create(['slug' => 'api-test', 'title' => 'T', 'body' => Sanitizer::html('<p>ok</p><script>x</script>'), 'is_published' => true]);

        $this->getJson('/api/v1/cms/pages/api-test')->assertOk()->assertJsonMissing(['body' => '<p>ok</p><script>x</script>']);
    }

    public function test_deleting_a_staff_member_removes_their_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/staff/x.jpg', 'fake');
        $member = CmsStaffMember::create(['name' => 'Photo Person', 'group' => 'STAFF', 'photo_path' => 'cms/staff/x.jpg', 'is_published' => true]);

        $this->actingAs($this->principal())->delete(route('cms.admin.destroy', ['staff', $member->id]))->assertRedirect();

        Storage::disk('public')->assertMissing('cms/staff/x.jpg');
        $this->assertDatabaseMissing('cms_staff_members', ['id' => $member->id]);
    }

    public function test_every_cms_change_is_audited(): void
    {
        $this->actingAs($this->principal())->post(route('cms.admin.store', 'notices'), [
            'title' => 'Audited notice', 'body' => 'x', 'is_published' => '1',
        ]);

        $this->assertDatabaseHas('audit_logs', ['module' => 'cms', 'action' => 'cms_notices_created']);
    }
}
