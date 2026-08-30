<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrontendPageController extends Controller
{
    public function about() { return view('frontend.pages.about'); }
    public function governingBody() { return view('frontend.pages.governing_body'); }
    public function chairmanProfile() { return view('frontend.pages.chairman_profile'); }
    public function messageChairman() { return view('frontend.pages.message_chairman'); }
    public function messagePrincipal() { return view('frontend.pages.message_principal'); }
    public function teacherInfo() { return view('frontend.pages.teacher_info'); }
    public function staffInfo() { return view('frontend.pages.staff_info'); }
    public function academicCalendar() { return view('frontend.pages.academic_calendar'); }
    public function programs() { return view('frontend.pages.programs'); }
    public function noticeBoard() { return view('frontend.pages.notice_board'); }
    public function admission() { return view('frontend.pages.admission'); }
    public function gallery() { return view('frontend.pages.gallery'); }
    public function contact() { return view('frontend.pages.contact'); }
}
