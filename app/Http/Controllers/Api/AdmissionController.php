<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\User;
use App\Services\AdmissionPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdmissionController extends Controller
{
    /**
     * Submit Admission Application (Handles both College Student and School Guardian Registration)
     */
    public function submit(Request $request)
    {
        // Explicitly distinguish School Guardian registration vs College Admission
        $isSchool = $request->boolean('is_school') 
            || $request->get('role') === 'guardian' 
            || ($request->filled('fullName') && !$request->filled('sscRoll'))
            || $request->get('class_name') === 'Guardian Portal Registration';

        $isCollege = !$isSchool && ($request->filled('sscRoll') 
            || stripos((string)$request->get('class_name'), 'XI') !== false 
            || stripos((string)$request->get('class_name'), 'HSC') !== false
            || $request->filled('department'));

        if ($isCollege) {
            $validator = Validator::make($request->all(), [
                'department' => 'required|string|max:30',
                'sscBoard' => 'required|string|max:30',
                'sscRoll' => 'required|string|max:20',
                'registrationNumber' => 'required|string|max:20',
                'passingYear' => 'required|string|max:10',
                'gpa' => 'required|numeric|between:1.00,5.00',
                'name' => 'required|string|max:50',
                'gender' => 'required|in:Male,Female,Other',
                'mobile' => 'required|string|size:11',
                'email' => 'required|email|max:60',
                'fatherName' => 'required|string|max:50',
                'motherName' => 'required|string|max:50',
                'guardianName' => 'required|string|max:50',
                'relationWithGuardian' => 'required|string|max:30',
                'guardianMobile' => 'required|string|size:11',
                'presentAddress' => 'required|string',
                'photo_id' => 'nullable|exists:media_files,id',
            ]);
        } else {
            // School Guardian Registration (No student or relationship required upfront)
            $validator = Validator::make($request->all(), [
                'guardianName' => 'required_without:fullName|nullable|string|max:100',
                'fullName' => 'required_without:guardianName|nullable|string|max:100',
                'guardianMobile' => 'required_without:phone|nullable|string|size:11',
                'phone' => 'required_without:guardianMobile|nullable|string|size:11',
                'guardianNid' => 'nullable|string|max:30',
                'guardianOccupation' => 'nullable|string|max:50',
                'guardianEmail' => 'nullable|email|max:60',
                'email' => 'nullable|email|max:60',
                'presentAddress' => 'nullable|string',
                'address' => 'nullable|string',
                'password' => 'nullable|string|min:6',
                'relationWithGuardian' => 'nullable|string|max:30',
                'photo_id' => 'nullable|exists:media_files,id',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // This endpoint needs no login, so it may only create a guardian account, never change one.
        if (!$isCollege) {
            $conflicts = $this->guardianSignupConflicts(
                $request->guardianMobile ?: $request->phone,
                $request->guardianEmail ?: $request->email,
            );

            if ($conflicts) {
                return response()->json(['status' => 'error', 'errors' => $conflicts], 422);
            }
        }

        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->has('photo_id') && $request->photo_id) {
                $media = \App\Models\MediaFile::find($request->photo_id);
                if ($media) {
                    $photoPath = $media->file_path;
                }
            }

            if ($isCollege) {
                // College Student Registration Flow
                $guardianPlainPassword = (string) random_int(100000, 999999);
                $guardian = User::where('role_id', 6)->where('phone_number', $request->guardianMobile)->first();
                if (!$guardian) {
                    $guardian = User::create([
                        'name' => $request->guardianName,
                        'email' => $request->guardianMobile . '@guardian.maestrocrown.edu.bd',
                        'phone_number' => $request->guardianMobile,
                        'role_id' => 6,
                        'password' => Hash::make($guardianPlainPassword),
                        'user_type' => 'regular',
                        'status' => 1,
                        'school_id' => 1,
                    ]);
                }

                $studentPassword = (string) random_int(100000, 999999);
                $studentId = 'MCC' . date('Y') . mt_rand(1000, 9999);
                $student = User::create([
                    'student_id' => $studentId,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_number' => $request->mobile,
                    'role_id' => 7,
                    'parent_id' => $guardian->id,
                    'password' => Hash::make($studentPassword),
                    'user_type' => 'regular',
                    'status' => 1,
                    'school_id' => 1,
                    'student_info' => json_encode([
                        'department' => $request->department,
                        'gender' => $request->gender,
                        'ssc_board' => $request->sscBoard,
                        'ssc_roll' => $request->sscRoll,
                        'ssc_gpa' => $request->gpa,
                    ]),
                ]);

                $tempAppNo = 'MCC-' . date('Y') . '-' . uniqid();
                $admission = Admission::create([
                    'user_id' => $student->id,
                    'guardian_user_id' => $guardian->id,
                    'application_no' => $tempAppNo,
                    'department' => $request->department,
                    'class_name' => 'Class XI (HSC)',
                    'ssc_board' => $request->sscBoard,
                    'ssc_roll' => $request->sscRoll,
                    'registration_number' => $request->registrationNumber,
                    'passing_year' => $request->passingYear,
                    'gpa' => $request->gpa,
                    'photo_path' => $photoPath,
                    'photo_id' => $request->photo_id,
                    'name' => $request->name,
                    'gender' => $request->gender,
                    'mobile' => $request->mobile,
                    'email' => $request->email,
                    'father_name' => $request->fatherName,
                    'mother_name' => $request->motherName,
                    'guardian_name' => $request->guardianName,
                    'relation_with_guardian' => $request->relationWithGuardian,
                    'guardian_mobile' => $request->guardianMobile,
                    'present_address' => $request->presentAddress,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                $year = date('Y');
                $paddedId = str_pad($admission->id, 6, '0', STR_PAD_LEFT);
                $applicationNo = "MCC-{$year}-{$paddedId}";
                $admission->update(['application_no' => $applicationNo]);

                DB::commit();

                $pdfUrl = $admission->slipUrl();

                return response()->json([
                    'status' => 'success',
                    'message' => 'College admission application submitted successfully.',
                    'data' => [
                        'application_no' => $applicationNo,
                        'id' => $admission->id,
                        'student_id' => $student->student_id,
                        'password' => $studentPassword,
                        'applicant_name' => $student->name,
                        'department' => $admission->department,
                        'role' => 'student',
                        'pdf_url' => $pdfUrl,
                    ]
                ], 201);
            }

            // School Guardian Registration Flow
            $guardianName = $request->guardianName ?: $request->fullName;
            $guardianMobile = $request->guardianMobile ?: $request->phone;
            $guardianEmail = $request->guardianEmail ?: $request->email;
            $presentAddress = $request->presentAddress ?: ($request->address ?: 'Dhaka, Bangladesh');
            $guardianNid = $request->guardianNid ?: ($request->nid ?: 'N/A');
            $guardianPlainPassword = $request->filled('password') ? $request->password : (string) random_int(100000, 999999);

            $guardianInfo = [
                'nid' => $guardianNid,
                'occupation' => $request->guardianOccupation ?: 'Guardian',
                'relation' => $request->relationWithGuardian ?: 'Guardian',
                'address' => $presentAddress,
                'phone' => $guardianMobile,
            ];

            $guardian = User::create([
                'name' => $guardianName,
                'email' => $guardianEmail ?: ($guardianMobile . '@guardian.maestrocrown.edu.bd'),
                'phone_number' => $guardianMobile,
                'role_id' => 6,
                'password' => Hash::make($guardianPlainPassword),
                'user_type' => 'regular',
                'status' => 1,
                'school_id' => 1,
                'user_information' => json_encode($guardianInfo),
            ]);

            DB::commit();

            $token = $guardian->createToken('guardian-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Guardian account registered successfully. Please login to your portal to enroll your student.',
                'token' => $token,
                'role' => 'guardian',
                'user' => $guardian,
                'data' => [
                    'id' => $guardian->id,
                    'guardian_name' => $guardian->name,
                    'guardian_mobile' => $guardian->phone_number,
                    'login_id' => $guardian->phone_number,
                    // Only a generated password is returned; a chosen one is already known to the user.
                    'password' => $request->filled('password') ? null : $guardianPlainPassword,
                    'role' => 'guardian',
                    'student_name' => null,
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Registration could not be completed. Please try again.'
            ], 500);
        }
    }

    /** @return array<string, string[]> field errors when the phone or email already belongs to someone */
    private function guardianSignupConflicts(string $mobile, ?string $email): array
    {
        $errors = [];

        if (User::where('role_id', 6)->where('phone_number', $mobile)->exists()) {
            $errors['phone'] = ['An account with this mobile number already exists. Please sign in, or contact the college office to reset your password.'];
        }

        if ($email && User::where('email', $email)->exists()) {
            $errors['email'] = ['This email address is already in use.'];
        }

        return $errors;
    }

    /**
     * Authenticated Guardian adds a student directly from their Guardian Portal
     */
    public function addStudentByGuardian(Request $request)
    {
        $guardian = $request->user();
        if ($guardian->role_id != 6) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only registered guardians can add students from this portal.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'className' => 'required|string|max:30',
            'gender' => 'required|in:Male,Female,Other',
            'relationWithStudent' => 'nullable|string|max:30',
            'dateOfBirth' => 'nullable|string|max:20',
            'birthCertificateNo' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:30',
            'photo_id' => 'nullable|exists:media_files,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->has('photo_id') && $request->photo_id) {
                $media = \App\Models\MediaFile::find($request->photo_id);
                if ($media) {
                    $photoPath = $media->file_path;
                }
            }

            $guardianInfo = json_decode($guardian->user_information, true) ?: [];
            $relation = $request->relationWithStudent ?: ($request->relationWithGuardian ?: ($guardianInfo['relation'] ?? 'Guardian'));
            $fatherName = ($relation === 'Father') ? $guardian->name : 'N/A';
            $motherName = ($relation === 'Mother') ? $guardian->name : 'N/A';

            // Create Student linked to this guardian
            $studentId = 'MCC' . date('Y') . mt_rand(1000, 9999);
            $student = User::create([
                'student_id' => $studentId,
                'name' => $request->name,
                'email' => $guardian->phone_number . '+' . $studentId . '@maestrocrown.edu.bd',
                'phone_number' => $guardian->phone_number,
                'role_id' => 7,
                'parent_id' => $guardian->id,
                'password' => Hash::make((string) random_int(100000, 999999)),
                'user_type' => 'regular',
                'status' => 1,
                'school_id' => 1,
                'student_info' => json_encode([
                    'class' => $request->className,
                    'gender' => $request->gender,
                    'relation' => $relation,
                    'dob' => $request->dateOfBirth,
                    'brc' => $request->birthCertificateNo,
                ]),
            ]);

            $tempAppNo = 'MCC-' . date('Y') . '-' . uniqid();

            $admission = Admission::create([
                'user_id' => $student->id,
                'guardian_user_id' => $guardian->id,
                'application_no' => $tempAppNo,
                'department' => $request->department ?: 'General',
                'class_name' => $request->className,
                'photo_path' => $photoPath,
                'photo_id' => $request->photo_id,
                'name' => $request->name,
                'gender' => $request->gender,
                'mobile' => $guardian->phone_number,
                'email' => $guardian->email,
                'birth_certificate_no' => $request->birthCertificateNo,
                'date_of_birth' => $request->dateOfBirth,
                'father_name' => $fatherName,
                'mother_name' => $motherName,
                'guardian_name' => $guardian->name,
                'relation_with_guardian' => $relation,
                'guardian_mobile' => $guardian->phone_number,
                'guardian_nid' => $guardianInfo['nid'] ?? '',
                'guardian_occupation' => $guardianInfo['occupation'] ?? '',
                'present_address' => $guardianInfo['address'] ?? '',
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            $year = date('Y');
            $paddedId = str_pad($admission->id, 6, '0', STR_PAD_LEFT);
            $applicationNo = "MCC-{$year}-{$paddedId}";
            $admission->update(['application_no' => $applicationNo]);

            DB::commit();

            $pdfUrl = $admission->slipUrl();

            return response()->json([
                'status' => 'success',
                'message' => 'Student successfully enrolled under your guardian profile.',
                'data' => [
                    'application_no' => $applicationNo,
                    'id' => $admission->id,
                    'student_id' => $student->student_id,
                    'student_name' => $student->name,
                    'class_name' => $admission->class_name,
                    'pdf_url' => $pdfUrl,
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'The student could not be added. Please try again.'
            ], 500);
        }
    }

    /**
     * Get all students linked to the authenticated guardian, with admission & payment info
     */
    public function guardianStudents(Request $request)
    {
        $guardian = $request->user();
        $students = User::where('parent_id', $guardian->id)
            ->where('role_id', 7)
            ->where('status', 1)
            ->get();

        $enriched = $students->map(function ($student) {
            $admission = Admission::where('user_id', $student->id)->latest()->first();

            $admissionData = null;
            if ($admission) {
                $pdfUrl = $admission->slipUrl();

                // Check fees payment status
                ['status' => $appFeeStatus, 'amount' => $appFeeAmount] = $admission->feeSummary('application_fee');
                ['status' => $admFeeStatus, 'amount' => $admFeeAmount] = $admission->feeSummary('admission_fee');

                // Derive exam date from class_name (school admissions get exam within 7 days of submission)
                $examDate = null;
                if ($admission->submitted_at) {
                    $examDate = $admission->submitted_at->addDays(7)->format('Y-m-d');
                }

                $admissionData = [
                    'application_no' => $admission->application_no,
                    'class_name' => $admission->class_name,
                    'status' => $admission->status, // pending, approved, rejected
                    'submitted_at' => $admission->submitted_at?->toDateTimeString(),
                    'reviewed_at' => $admission->reviewed_at?->toDateTimeString(),
                    'rejection_reason' => $admission->rejection_reason,
                    'exam_date' => $examDate,
                    'pdf_url' => $pdfUrl,
                    'payment_status' => $appFeeStatus,
                    'payment_amount' => $appFeeAmount,
                    'application_fee_status' => $appFeeStatus,
                    'application_fee' => $appFeeAmount,
                    'admission_fee_status' => $admFeeStatus,
                    'admission_fee' => $admFeeAmount,
                ];
            }

            $student->admission = $admissionData;
            return $student;
        });

        return response()->json([
            'status' => 'success',
            'data' => $enriched
        ], 200);
    }

    /**
     * Download admission slip PDF. Allowed with a signed link from the API, or for the signed-in
     * student or guardian the application belongs to.
     */
    public function downloadPdf(Request $request, $application_no)
    {
        $admission = Admission::where('application_no', $application_no)->first();

        $allowed = $admission
            && ($request->hasValidSignature(false) || $admission->isOwnedBy(auth('sanctum')->user()));

        if (!$allowed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admission application not found.'
            ], 404);
        }

        // Always generate fresh to guarantee dynamic status (approved/pending) and payment status (paid/unpaid)
        $pdfPath = (new AdmissionPdfService())->generate($admission);

        if (!$pdfPath || !Storage::disk('local')->exists($pdfPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF.'
            ], 500);
        }

        return Storage::disk('local')->download($pdfPath, 'Admission_Slip_' . $admission->application_no . '.pdf');
    }

    /**
     * View user's admission application
     */
    public function myApplication(Request $request)
    {
        $user = $request->user();
        
        $admission = Admission::where('guardian_user_id', $user->id)
            ->orWhere('user_id', $user->id)
            ->latest()
            ->first();

        if (!$admission) {
            return response()->json([
                'status' => 'error',
                'message' => 'No application found.'
            ], 404);
        }

        $pdfUrl = $admission->slipUrl();
        $photoUrl = $admission->photo_url;

        ['status' => $appFeeStatus, 'amount' => $appFeeAmount] = $admission->feeSummary('application_fee');
        ['status' => $admFeeStatus, 'amount' => $admFeeAmount] = $admission->feeSummary('admission_fee');

        return response()->json([
            'status' => 'success',
            'data' => [
                'application_no' => $admission->application_no,
                'name' => $admission->name,
                'guardian_name' => $admission->guardian_name,
                'guardian_mobile' => $admission->guardian_mobile,
                'class_name' => $admission->class_name,
                'department' => $admission->department,
                'student_id' => $admission->user_id,
                'photo_url' => $photoUrl,
                'pdf_url' => $pdfUrl,
                'status' => $admission->status,
                'payment_status' => $appFeeStatus,
                'payment_amount' => $appFeeAmount,
                'application_fee_status' => $appFeeStatus,
                'application_fee' => $appFeeAmount,
                'admission_fee_status' => $admFeeStatus,
                'admission_fee' => $admFeeAmount,
            ]
        ], 200);
    }
}
