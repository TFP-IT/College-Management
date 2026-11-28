<?php

namespace App\Modules\Campus\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Printable marksheet and admit card. They open from a signed link that expires, so a student
 * or guardian can print them from the phone without signing in to the website.
 */
class DocumentController extends Controller
{
    public function __construct(private ResultService $results)
    {
    }

    public static function marksheetUrl(int $student, int $publication): string
    {
        return url(URL::temporarySignedRoute('document.marksheet', now()->addDay(), ['student' => $student, 'publication' => $publication], false));
    }

    public static function admitCardUrl(int $student, int $category): string
    {
        return url(URL::temporarySignedRoute('document.admit', now()->addDay(), ['student' => $student, 'category' => $category], false));
    }

    private function context(int $student): StudentContext
    {
        $ctx = StudentContext::for(User::where('role_id', StudentContext::STUDENT)->findOrFail($student));
        abort_unless($ctx->enrolled(), 404);

        return $ctx;
    }

    private function shared(StudentContext $ctx): array
    {
        return [
            'student' => $ctx->student,
            'school' => DB::table('schools')->where('id', $ctx->schoolId)->first(),
            'class' => DB::table('classes')->where('id', $ctx->classId)->value('name'),
            'section' => DB::table('sections')->where('id', $ctx->sectionId)->value('name'),
        ];
    }

    public function marksheet(int $student, int $publication)
    {
        $ctx = $this->context($student);
        // Looking the result up among the published ones keeps withdrawn marks private.
        $result = collect($this->results->publishedFor($ctx))->firstWhere('id', $publication);
        abort_unless($result, 404, 'This result is not published.');

        return view('office.documents.marksheet', $this->shared($ctx) + ['result' => $result]);
    }

    public function admitCard(int $student, int $category)
    {
        $ctx = $this->context($student);
        $papers = collect($this->results->upcomingExams($ctx, 50))->where('category_id', $category)->values();
        abort_if($papers->isEmpty(), 404, 'No coming papers for this examination.');

        return view('office.documents.admit', $this->shared($ctx) + [
            'papers' => $papers,
            'exam' => DB::table('exam_categories')->where('id', $category)->value('name') ?: $papers[0]['name'],
        ]);
    }
}
