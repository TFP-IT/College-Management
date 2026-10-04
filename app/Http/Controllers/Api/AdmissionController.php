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
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department' => 'required|in:Science,Commerce,Arts',
            'sscBoard' => 'required|string',
            'sscRoll' => 'required|string|max:6',
            'registrationNumber' => 'required|string|max:10',
            'passingYear' => 'required|string|size:4',
            'gpa' => 'required|numeric|between:0.00,5.00',
            'name' => 'required|string|max:30',
            'gender' => 'required|in:Male,Female',
            'mobile' => 'required|string|size:11',
            'email' => 'required|email',
            'fatherName' => 'required|string|max:30',
            'motherName' => 'required|string|max:30',
            'guardianName' => 'required|string|max:30',
            'relationWithGuardian' => 'required|string|max:30',
            'guardianMobile' => 'required|string|size:11',
            'presentAddress' => 'required|string',
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
            if ($request->has('photo_id')) {
                $media = \App\Models\MediaFile::find($request->photo_id);
                if ($media) {
                    $photoPath = $media->file_path;
                }
            }

            // Generate a temporary application_no to pass unique constraint if needed
            $tempAppNo = '2026-' . uniqid();

            // Create temporary guardian account
            $guardian = User::create([
                'name' => $request->guardianName,
                'email' => null,
                'phone_number' => $request->guardianMobile,
                'role_id' => 6,
                'password' => Hash::make((string) mt_rand(10000000, 99999999)),
                'user_type' => 'temp',
                'status' => 1,
                'school_id' => 1,
            ]);

            // Create temporary student account
            $studentId = '2026' . time();
            $studentPassword = (string) mt_rand(10000000, 99999999);
            $student = User::create([
                'student_id' => $studentId,
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->mobile,
                'role_id' => 7,
                'parent_id' => $guardian->id,
                'password' => Hash::make($studentPassword),
                'user_type' => 'temp',
                'status' => 1,
                'school_id' => 1,
            ]);

            $admission = Admission::create([
                'user_id' => $student->id,
                'guardian_user_id' => $guardian->id,
                'application_no' => $tempAppNo,
                'department' => $request->department,
                'ssc_board' => $request->sscBoard,
                'ssc_roll' => $request->sscRoll,
                'registration_number' => $request->registrationNumber,
                'passing_year' => $request->passingYear,
                'gpa' => $request->gpa,
                'photo_path' => $photoPath,
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

            // Generate final format based on ID
            $year = date('Y');
            $paddedId = str_pad($admission->id, 6, '0', STR_PAD_LEFT);
            $applicationNo = "MCC-{$year}-{$paddedId}";
            
            $admission->update(['application_no' => $applicationNo]);

            DB::commit();

            // Generate PDF Slip
            $pdfService = new AdmissionPdfService();
            $pdfPath = $pdfService->generate($admission);
            $pdfUrl = $pdfPath ? Storage::disk('public')->url($pdfPath) : null;

            return response()->json([
                'status' => 'success',
                'message' => 'Admission application submitted successfully.',
                'data' => [
                    'application_no' => $applicationNo,
                    'id' => $admission->id,
                    'student_id' => $student->student_id,
                    'password' => $studentPassword,
                    'pdf_url' => $pdfUrl,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit application: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadPdf($application_no)
    {
        $admission = Admission::where('application_no', $application_no)->first();

        if (!$admission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admission application not found.'
            ], 404);
        }

        $pdfService = new AdmissionPdfService();
        
        if (!$admission->pdf_path || !Storage::disk('public')->exists($admission->pdf_path)) {
            $pdfPath = $pdfService->generate($admission);
            if (!$pdfPath) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate PDF.'
                ], 500);
            }
        }

        return Storage::disk('public')->download($admission->pdf_path, 'Admission_Slip_' . $application_no . '.pdf');
    }

    public function myApplication(Request $request)
    {
        $user = $request->user();
        // Since Admission uses user_id, we can find it
        $admission = Admission::where('user_id', $user->id)->latest()->first();

        if (!$admission) {
            return response()->json([
                'status' => 'error',
                'message' => 'No application found.'
            ], 404);
        }

        $pdfUrl = $admission->pdf_path ? Storage::disk('public')->url($admission->pdf_path) : null;
        $photoUrl = $admission->photo_path ? Storage::disk('public')->url($admission->photo_path) : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'application_no' => $admission->application_no,
                'name' => $admission->name,
                'father_name' => $admission->father_name,
                'department' => $admission->department,
                'student_id' => $user->student_id,
                'photo_url' => $photoUrl,
                'pdf_url' => $pdfUrl,
                'status' => $admission->status,
            ]
        ], 200);
    }
}
