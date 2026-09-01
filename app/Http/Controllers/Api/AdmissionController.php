<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\User;
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
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
            if ($request->hasFile('image')) {
                $photoPath = $request->file('image')->store('admissions/photos', 'public');
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

            return response()->json([
                'status' => 'success',
                'message' => 'Admission application submitted successfully.',
                'data' => [
                    'application_no' => $applicationNo,
                    'id' => $admission->id,
                    'student_id' => $student->student_id,
                    'password' => $studentPassword,
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
}
