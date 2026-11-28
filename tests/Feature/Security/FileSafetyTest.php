<?php

namespace Tests\Feature\Security;

use App\Models\StudentFeeManager;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileSafetyTest extends SecurityTestCase
{
    private array $written = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    /** A real temporary file, so the type is judged from its content exactly as in production. */
    private function script(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, "<?php echo 'x';");
        $this->written[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    private function invoice(User $student, ?User $parent = null): StudentFeeManager
    {
        return StudentFeeManager::create([
            'title' => 'Tuition', 'total_amount' => 1000, 'amount' => 1000, 'class_id' => 1,
            'student_id' => $student->id, 'parent_id' => $parent?->id, 'paid_amount' => 0,
            'status' => 'unpaid', 'school_id' => $this->schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);
    }

    public function test_anonymous_upload_accepts_only_real_images(): void
    {
        foreach (['shell.php', 'shell.php.jpg', 'photo.jpg', '.htaccess', 'page.html'] as $name) {
            $this->postJson('/api/media/upload', ['file' => $this->script($name)])->assertStatus(422);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->postJson('/api/media/upload', ['file' => UploadedFile::fake()->image('me.jpg')])->assertCreated();
        $this->assertStringEndsWith('.jpg', Storage::disk('public')->allFiles()[0]);
    }

    public function test_web_screens_refuse_script_uploads(): void
    {
        $student = $this->user(7);
        $invoice = $this->invoice($student);

        $this->actingAs($student)
            ->post("/student/student_fee/offline_payment/{$invoice->id}", ['amount' => 1000, 'document_image' => $this->script('shell.php')])
            ->assertSessionHasErrors();

        $this->assertFileDoesNotExist(public_path('assets/uploads/offline_payment/shell.php'));
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_offline_payment_proof_gets_a_random_name(): void
    {
        $parent = $this->user(6);
        $invoice = $this->invoice($this->user(7, ['parent_id' => $parent->id]), $parent);

        $this->actingAs($parent)
            ->post("/parent/student_fee/offline_payment/{$invoice->id}", ['amount' => 1000, 'document_image' => UploadedFile::fake()->image('slip.png')])
            ->assertSessionHasNoErrors()->assertRedirect();

        $stored = (string) $invoice->fresh()->document_image;
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}\.png$/', $stored);
        $path = public_path('assets/uploads/offline_payment/' . $stored);
        $this->written[] = $path;
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertSame('offline', $invoice->fresh()->payment_method);
        $this->assertFileExists($path);
    }

    public function test_invoices_of_other_families_are_not_found(): void
    {
        $theirs = $this->invoice($this->user(7), $this->user(6));
        $parent = $this->user(6);
        $student = $this->user(7);

        $this->actingAs($parent)->get("/parent/student_fee/invoice/{$theirs->id}")->assertNotFound();
        $this->actingAs($parent)->post("/parent/student_fee/offline_payment/{$theirs->id}", ['amount' => 1])->assertNotFound();
        $this->actingAs($student)->get("/student/student_fee/invoice/{$theirs->id}")->assertNotFound();
        $this->actingAs($student)->get("/student/fee_manager/payment/{$theirs->id}")->assertNotFound();
        $this->actingAs($student)->post("/student/student_fee/offline_payment/{$theirs->id}", ['amount' => 1])->assertNotFound();
        $this->assertSame('unpaid', $theirs->fresh()->status);
    }

    public function test_admission_slip_needs_a_signed_link_or_the_owner(): void
    {
        $guardian = $this->user(6);
        $admission = $this->admission($this->user(7, ['parent_id' => $guardian->id]), $guardian);
        $plain = "/api/admission/{$admission->application_no}/pdf";

        $this->get($plain)->assertNotFound();
        $this->get($plain . '?signature=forged&expires=' . (time() + 60))->assertNotFound();
        $this->withToken($this->user(6)->createToken('t')->plainTextToken)->get($plain)->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->get($admission->slipUrl())->assertOk()->assertDownload();
        $this->withToken($guardian->createToken('t')->plainTextToken)->get($plain)->assertOk();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_slip_link_expires(): void
    {
        $admission = $this->admission($this->user(7));
        $url = $admission->slipUrl();

        $this->travel(config('security.slip_link_hours') + 1)->hours();

        $this->get($url)->assertNotFound();
    }
}
