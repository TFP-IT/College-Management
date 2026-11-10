<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TeacherAuthController;
use App\Http\Controllers\Api\TeacherProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();
    if ($user && $user->role_id == 6) {
        $guardianInfo = json_decode($user->user_information, true) ?: [];
        $latestAdmission = \App\Models\Admission::where('guardian_user_id', $user->id)
            ->latest()
            ->first();

        $user->guardian_details = [
            'nid' => $guardianInfo['nid'] ?? ($latestAdmission->guardian_nid ?? 'N/A'),
            'occupation' => $guardianInfo['occupation'] ?? ($latestAdmission->guardian_occupation ?? 'Guardian'),
            'relation' => $guardianInfo['relation'] ?? ($latestAdmission->relation_with_guardian ?? 'Guardian'),
            'address' => $guardianInfo['address'] ?? ($latestAdmission->present_address ?? 'Not specified'),
            'application_no' => $latestAdmission->application_no ?? null,
            'children_count' => \App\Models\User::where('parent_id', $user->id)->where('role_id', 7)->count(),
        ];
    } elseif ($user && $user->role_id == 7) {
        $admission = \App\Models\Admission::where('user_id', $user->id)->latest()->first();
        if ($admission) {
            $feePayment = $admission->admissionFeePayment();
            $paymentStatus = 'unpaid';
            $paymentAmount = 500.00;
            if ($feePayment) {
                $paymentStatus = $feePayment->status;
                $paymentAmount = (float) $feePayment->amount;
            }
            $admission->payment_status = $paymentStatus;
            $admission->payment_amount = $paymentAmount;
            $admission->application_fee = $paymentAmount;
            $admission->admission_fee = $paymentAmount;

            $user->admission = $admission;
            $user->department = $admission->department;
            $user->class_name = $admission->class_name;
        }
    }
    return response()->json([
        'status' => 'success',
        'data' => $user
    ]);
});

Route::post('/login', [ApiController::class, 'login']);
Route::post('/app_login', [ApiController::class, 'appLogin']);

// Teacher First-Time Setup & Password Recovery (Email OTP)
Route::prefix('auth/teacher')->group(function () {
    Route::post('/request-setup-otp', [TeacherAuthController::class, 'requestSetupOtp']);
    Route::post('/verify-setup-otp', [TeacherAuthController::class, 'verifySetupOtp']);
    Route::post('/complete-setup', [TeacherAuthController::class, 'completeSetup']);
});

Route::post('/media/upload', [MediaController::class, 'upload']);
Route::post('/admission/submit', [AdmissionController::class, 'submit']);
Route::get('/admission/{application_no}/pdf', [AdmissionController::class, 'downloadPdf']);

// Teacher & Classroom API Routes
Route::middleware(['auth:sanctum', 'api.teacher'])->prefix('teacher')->group(function () {
    Route::get('/profile-360', [TeacherProfileController::class, 'show']);
    Route::get('/students', [ApiController::class, 'teacherStudents']);
    Route::get('/classes', [ApiController::class, 'teacherClasses']);
    Route::get('/threads', [ApiController::class, 'teacherThreads']);
    Route::post('/attendance/submit', [ApiController::class, 'submitAttendance']);
});

// Payment Gateway Routes (SSLCommerz)
// Callbacks (Without auth middleware because SSLCommerz sends the POST request directly to the server)
Route::any('/payment/success', [PaymentController::class, 'paymentSuccess']);
Route::any('/payment/fail', [PaymentController::class, 'paymentFail']);
Route::any('/payment/cancel', [PaymentController::class, 'paymentCancel']);
Route::get('/payment/result', function (Request $request) {
    $status = $request->get('status', 'success');
    $isSuccess = $status === 'success';
    $title = $isSuccess ? 'Payment Successful' : 'Payment Failed / Cancelled';
    $icon = $isSuccess ? '✅' : '❌';
    $message = $isSuccess 
        ? 'Your admission fee payment has been successfully processed.' 
        : 'The transaction could not be completed or was cancelled.';

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f1f5f9; text-align: center; padding: 20px; box-sizing: border-box; }
    .card { background: #ffffff; padding: 36px 24px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); max-width: 360px; width: 100%; }
    .icon { font-size: 50px; margin-bottom: 16px; }
    h2 { margin: 0 0 10px; color: #0f172a; font-size: 20px; font-weight: 700; }
    p { margin: 0 0 20px; color: #64748b; font-size: 14px; line-height: 20px; }
    .badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .success { background: #dcfce7; color: #166534; }
    .failed { background: #fee2e2; color: #991b1b; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">{$icon}</div>
    <h2>{$title}</h2>
    <p>{$message}</p>
    <div class="badge {$status}">Returning to Maestro Crown College App...</div>
  </div>
</body>
</html>
HTML;

    return response($html, 200)->header('Content-Type', 'text/html');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment']);
    Route::post('/user_details', [ApiController::class, 'userDetails']);
    Route::post('/routine', [ApiController::class, 'routine']);
    Route::post('/attendance', [ApiController::class, 'attendanceReport']);
    Route::post('/subjects', [ApiController::class, 'subjects']);
    Route::post('/syllabus_list', [ApiController::class, 'syllabus_list']);
    Route::post('/teacher_list', [ApiController::class, 'teacher_list']);
    Route::post('/book_list', [ApiController::class, 'book_list']);
    Route::post('/book_issue_list', [ApiController::class, 'book_issue_list']);
    Route::post('/exam_list', [ApiController::class, 'exam_list']);
    Route::post('/marks', [ApiController::class, 'marks']);
    Route::post('/profile_update', [ApiController::class, 'profile_update']);
    Route::post('/fee_list', [ApiController::class, 'fee_list']);
    Route::post('/logout', [ApiController::class, 'logout']);
    Route::post('/account_delete', [ApiController::class, 'account_delete']);
    Route::post('/change_profile_photo', [ApiController::class, 'change_profile_photo']);
    Route::post('/change_password', [ApiController::class, 'changePassword']);
    Route::post('/my_application', [AdmissionController::class, 'myApplication']);
    Route::post('/guardian/add_student', [AdmissionController::class, 'addStudentByGuardian']);
    Route::get('/guardian/students', [AdmissionController::class, 'guardianStudents']);
});

// MCC-Hello Future Admissions API v1 Routes
if (file_exists(__DIR__ . '/admissions_api.php')) {
    require __DIR__ . '/admissions_api.php';
}
if (file_exists(__DIR__ . '/cms_api.php')) {
    require __DIR__ . '/cms_api.php';
}
