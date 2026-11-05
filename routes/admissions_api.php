<?php

use App\Modules\Admissions\Http\Controllers\Api\V1\ApplicationApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\CouncilApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\GuardianApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\OtpApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\PaymentApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\ProgramApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\StaffApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\StaffAuthApiController;
use App\Modules\Admissions\Http\Controllers\Api\V1\TrackApiController;
use Illuminate\Support\Facades\Route;

/*
| API v1 for admissions. Every route here is listed in docs/ADMISSIONS_API.md.
| - Applicant routes need a token with the "applicant" ability (from OTP verification).
| - Staff routes need a staff token AND the matching permission (see AdmissionsRbacSeeder).
| - Old paths (apply, auth/otp/*, payments/initiate) remain as aliases for the Flutter app.
*/
Route::prefix('v1/admissions')->name('api.v1.admissions.')->group(function () {

    // ---- Public discovery -------------------------------------------------
    Route::get('programs', [ProgramApiController::class, 'index'])->name('programs.index');
    Route::get('programs/{id}', [ProgramApiController::class, 'show'])->name('programs.show');

    // ---- Applicant identity (OTP) ----------------------------------------
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('applicants/otp/request', [OtpApiController::class, 'send'])->name('otp.request');
        Route::post('applicants/otp/verify', [OtpApiController::class, 'verify'])->name('otp.verify');
        Route::post('auth/otp/send', [OtpApiController::class, 'send'])->name('otp.send');
        Route::post('auth/otp/verify', [OtpApiController::class, 'verify'])->name('otp.verify.legacy');

        // Guardian sign-in by email code (same steps as the website). The token can then apply.
        Route::post('guardian/otp/request', [GuardianApiController::class, 'requestOtp'])->name('guardian.otp.request');
        Route::post('guardian/otp/verify', [GuardianApiController::class, 'verifyOtp'])->name('guardian.otp.verify');
    });

    // Public tracking: application number + phone + OTP code.
    Route::post('track', [TrackApiController::class, 'track'])->middleware('throttle:10,1')->name('track');

    // ---- Applicant (token with "applicant" ability) ----------------------
    Route::middleware(['auth:sanctum', 'ability:applicant,*'])->group(function () {
        Route::post('apply', [ApplicationApiController::class, 'apply'])->name('apply');
        Route::post('applications', [ApplicationApiController::class, 'apply'])->name('applications.store');
        Route::get('applications/{id}', [ApplicationApiController::class, 'show'])->name('applications.show');
        Route::match(['put', 'patch'], 'applications/{id}', [ApplicationApiController::class, 'update'])->name('applications.update');
        Route::post('applications/{id}/documents', [ApplicationApiController::class, 'uploadDocument'])->name('applications.documents.upload');
        Route::get('applications/{id}/documents', [ApplicationApiController::class, 'documents'])->name('applications.documents.index');
        Route::post('applications/{id}/submit', [ApplicationApiController::class, 'submit'])->name('applications.submit');
        Route::get('applications/{id}/status', [ApplicationApiController::class, 'show'])->name('applications.status');
        Route::post('applications/{id}/withdraw', [ApplicationApiController::class, 'withdraw'])->name('applications.withdraw');
        Route::post('applications/{id}/pay', [PaymentApiController::class, 'initiate'])->name('applications.pay');
        Route::post('payments/initiate', [PaymentApiController::class, 'initiateFromBody'])->name('payments.initiate');
        Route::get('payments/{ref}', [PaymentApiController::class, 'show'])->where('ref', 'TRX_[A-Za-z0-9_]+')->name('payments.show');
    });

    // ---- Payment gateway callbacks (no login; verified by signature or gateway API)
    Route::match(['get', 'post'], 'payments/callback/{provider}', [PaymentApiController::class, 'callback'])->name('payments.callback.provider');
    Route::match(['get', 'post'], 'payments/callback', [PaymentApiController::class, 'callback'])->name('payments.callback');

    // ---- Staff authentication --------------------------------------------
    Route::post('auth/login', [StaffAuthApiController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [StaffAuthApiController::class, 'logout'])->name('auth.logout');
        Route::post('auth/refresh', [StaffAuthApiController::class, 'refresh'])->name('auth.refresh');
    });

    // ---- Council & staff (token + permission) ----------------------------
    Route::middleware(['auth:sanctum', 'ability:staff,*'])->group(function () {
        Route::get('council/dashboard', [CouncilApiController::class, 'dashboard'])
            ->middleware('can:admission.report.view')->name('council.dashboard');

        Route::get('council/applications', [CouncilApiController::class, 'index'])
            ->middleware('can:admission.application.view')->name('council.applications.index');
        Route::get('council/applications/{id}', [CouncilApiController::class, 'show'])
            ->middleware('can:admission.application.view')->name('council.applications.show');
        Route::post('council/applications/{id}/decision', [CouncilApiController::class, 'recordDecision'])
            ->middleware('can:admission.decide')->name('council.applications.decision');
        Route::post('council/decision', [CouncilApiController::class, 'recordDecisionFromBody'])
            ->middleware('can:admission.decide')->name('council.decision');

        Route::post('officer/applications/{id}/verify-document/{docId}', [StaffApiController::class, 'verifyDocument'])
            ->middleware('can:admission.document.verify')->name('officer.documents.verify');
        Route::post('documents/{id}/verify', [StaffApiController::class, 'verifyDocumentById'])
            ->middleware('can:admission.document.verify')->name('documents.verify');

        Route::post('officer/applications/{id}/schedule-assessment', [StaffApiController::class, 'scheduleAssessment'])
            ->middleware('can:admission.assessment.record')->name('officer.assessments.schedule');
        Route::post('interviews', [StaffApiController::class, 'scheduleAssessmentFromBody'])
            ->middleware('can:admission.assessment.record')->name('interviews.store');
        Route::post('officer/applications/{id}/assessments/{assessmentId}/score', [StaffApiController::class, 'recordScore'])
            ->middleware('can:admission.assessment.record')->name('officer.assessments.score');
        Route::post('interviews/{id}/score', [StaffApiController::class, 'recordScoreById'])
            ->middleware('can:admission.assessment.record')->name('interviews.score');

        Route::post('officer/applications/{id}/enroll', [StaffApiController::class, 'enroll'])
            ->middleware('can:admission.enroll')->name('officer.enroll');
        Route::post('enroll', [StaffApiController::class, 'enrollFromBody'])
            ->middleware('can:admission.enroll')->name('enroll');
        Route::get('classes', [StaffApiController::class, 'classes'])
            ->middleware('can:admission.enroll')->name('classes.index');
        Route::get('sections', [StaffApiController::class, 'sections'])
            ->middleware('can:admission.enroll')->name('sections.index');

        Route::post('qr/verify', [StaffApiController::class, 'verifyQr'])
            ->middleware('can:admission.application.view')->name('qr.verify');
    });
});
