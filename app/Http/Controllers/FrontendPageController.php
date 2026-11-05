<?php

namespace App\Http\Controllers;

use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Cms\Models\CmsGalleryItem;
use App\Modules\Cms\Models\CmsNotice;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsStaffMember;
use App\Modules\Cms\Support\Sanitizer;

class FrontendPageController extends Controller
{
    /** Shows a CMS-edited page when one is published for this slug, otherwise the built-in text. */
    private function pageOr(string $slug, string $fallbackView)
    {
        $page = CmsPage::published()->where('slug', $slug)->first();

        return $page
            ? view('frontend.pages.cms_page', ['page' => $page, 'body' => Sanitizer::html($page->body)])
            : view($fallbackView);
    }

    public function about() { return $this->pageOr('about', 'frontend.pages.about'); }
    public function messageChairman() { return $this->pageOr('message-chairman', 'frontend.pages.message_chairman'); }
    public function messagePrincipal() { return $this->pageOr('message-principal', 'frontend.pages.message_principal'); }

    public function governingBody()
    {
        return view('frontend.pages.governing_body', [
            'members' => CmsStaffMember::published('GOVERNING_BODY')->get(),
        ]);
    }

    public function teacherInfo()
    {
        return view('frontend.pages.teacher_info', [
            'members' => CmsStaffMember::published('TEACHER')->get(),
        ]);
    }

    public function staffInfo()
    {
        return view('frontend.pages.staff_info', [
            'members' => CmsStaffMember::published('STAFF')->get(),
        ]);
    }

    public function noticeBoard()
    {
        return view('frontend.pages.notice_board', [
            'notices' => CmsNotice::live()->get(),
        ]);
    }

    public function gallery()
    {
        return view('frontend.pages.gallery', [
            'items' => CmsGalleryItem::published()->get(),
        ]);
    }

    public function chairmanProfile() { return view('frontend.pages.chairman_profile'); }
    public function academicCalendar() { return view('frontend.pages.academic_calendar'); }
    public function admission() { return $this->pageOr('admission', 'frontend.pages.admission'); }

    public function programs()
    {
        return view('frontend.pages.programs', [
            'programs' => AdmissionProgram::with(['academicClass', 'academicGroup'])->active()->orderBy('created_at')->get(),
        ]);
    }
    public function contact() { return view('frontend.pages.contact'); }
}
