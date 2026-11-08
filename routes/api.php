<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PaymentController;

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

Route::post('/media/upload', [MediaController::class, 'upload']);
Route::post('/admission/submit', [AdmissionController::class, 'submit']);
Route::get('/admission/{application_no}/pdf', [AdmissionController::class, 'downloadPdf']);

// Teacher & Classroom API Routes
Route::get('/teacher/students', [ApiController::class, 'teacherStudents']);
Route::get('/teacher/classes', [ApiController::class, 'teacherClasses']);
Route::get('/teacher/threads', [ApiController::class, 'teacherThreads']);
Route::post('/teacher/attendance/submit', [ApiController::class, 'submitAttendance']);

// Payment Gateway Routes (SSLCommerz)
// Callbacks (Without auth middleware because SSLCommerz sends the POST request directly to the server)
Route::any('/payment/success', [PaymentController::class, 'paymentSuccess']);
Route::any('/payment/fail', [PaymentController::class, 'paymentFail']);
Route::any('/payment/cancel', [PaymentController::class, 'paymentCancel']);
Route::get('/payment/result', function () {
    return response("Payment Processed. You can close this window.", 200);
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
