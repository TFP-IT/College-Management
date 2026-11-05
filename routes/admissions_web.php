<?php

use App\Modules\Admissions\Http\Controllers\Web\ApplicantPortalController;
use App\Modules\Admissions\Http\Controllers\Web\GuardianPortalController;
use App\Modules\Admissions\Http\Controllers\Web\MockPaymentWebController;
use App\Modules\Admissions\Http\Controllers\Web\StaffWebController;
use Illuminate\Support\Facades\Route;

/*
| Public applicant portal. Applicants prove their mobile number with a one-time code.
| Their session then remembers which application they may view or pay for.
*/
Route::prefix('admissions')->name('admissions.')->group(function () {
    Route::get('/', [ApplicantPortalController::class, 'index'])->name('index');
    Route::get('/apply/{program}', [ApplicantPortalController::class, 'apply'])->name('apply');
    Route::post('/apply/{program}', [ApplicantPortalController::class, 'store'])->name('store')->middleware('throttle:5,1');

    Route::post('/otp/send', [ApplicantPortalController::class, 'sendCode'])->name('otp.send')->middleware('throttle:10,1');
    Route::post('/otp/verify', [ApplicantPortalController::class, 'verifyCode'])->name('otp.verify')->middleware('throttle:10,1');

    // Guardian sign-in (email code), then the guardian portal.
    Route::get('/guardian/{program}/start', [GuardianPortalController::class, 'start'])->name('guardian.start');
    Route::post('/guardian/{program}/send', [GuardianPortalController::class, 'sendCode'])->name('guardian.send')->middleware('throttle:10,1');
    Route::post('/guardian/verify', [GuardianPortalController::class, 'verify'])->name('guardian.verify.post')->middleware('throttle:10,1');

    Route::get('/track', [ApplicantPortalController::class, 'track'])->name('track');
    Route::post('/track/send', [ApplicantPortalController::class, 'trackSendCode'])->name('track.send')->middleware('throttle:10,1');
    Route::post('/track/verify', [ApplicantPortalController::class, 'trackVerify'])->name('track.verify')->middleware('throttle:10,1');

    Route::get('/applications/{id}', [ApplicantPortalController::class, 'show'])->name('show');
    Route::get('/documents/{document}', [ApplicantPortalController::class, 'downloadDocument'])->name('documents.download')->middleware('signed');
    Route::get('/qr/{token}', [ApplicantPortalController::class, 'verifyQr'])->name('qr.verify');

    // Payments. The applicant's session must match the application (see MockPaymentWebController).
    Route::get('/payments/initiate/{applicationId}', [MockPaymentWebController::class, 'initiate'])->name('payments.initiate');
    Route::get('/payments/mock/{payment}', [MockPaymentWebController::class, 'checkout'])->name('payments.mock.checkout');
    Route::post('/payments/mock/{payment}', [MockPaymentWebController::class, 'process'])->name('payments.mock.process');
    Route::match(['get', 'post'], '/payments/callback/{payment}', [MockPaymentWebController::class, 'callback'])->name('payments.callback');
    Route::post('/payments/ipn', [MockPaymentWebController::class, 'ipn'])->name('payments.ipn');
});

/*
| Council and admissions desk. Each action needs its own permission.
*/
Route::prefix('admin/admissions')->name('admissions.admin.')->middleware('auth')->group(function () {
    Route::get('/', [StaffWebController::class, 'dashboard'])->middleware('can:admission.report.view')->name('dashboard');
    Route::get('/applications/{id}', [StaffWebController::class, 'show'])->middleware('can:admission.application.view')->name('show');
    Route::post('/applications/{applicationId}/verify-document/{documentId}', [StaffWebController::class, 'verifyDocument'])->middleware('can:admission.document.verify')->name('documents.verify');
    Route::post('/applications/{applicationId}/schedule-assessment', [StaffWebController::class, 'scheduleAssessment'])->middleware('can:admission.assessment.record')->name('assessments.schedule');
    Route::post('/applications/{applicationId}/assessments/{assessmentId}/score', [StaffWebController::class, 'recordScore'])->middleware('can:admission.assessment.record')->name('assessments.score');
    Route::post('/applications/{applicationId}/decision', [StaffWebController::class, 'recordDecision'])->middleware('can:admission.decide')->name('decision');
    Route::post('/applications/{applicationId}/enroll', [StaffWebController::class, 'enroll'])->middleware('can:admission.enroll')->name('enroll');
});

/*
| Guardian portal (sidebar). Requires a guardian signed in with an email code.
*/
Route::prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/', [GuardianPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/apply/{program}', [GuardianPortalController::class, 'apply'])->name('apply');
    Route::post('/logout', [GuardianPortalController::class, 'logout'])->name('logout');
});
