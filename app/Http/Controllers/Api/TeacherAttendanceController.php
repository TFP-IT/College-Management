<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeacherClassroomService;
use Illuminate\Http\Request;

class TeacherAttendanceController extends Controller
{
    public function show(Request $request, TeacherClassroomService $classroom)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $classroom->attendanceSheet(
                $request->user(),
                (int) $data['class_id'],
                (int) $data['section_id'],
                $data['date']
            ),
        ]);
    }

    public function store(Request $request, TeacherClassroomService $classroom)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|integer',
            'records.*.status' => 'required|in:present,late,absent',
            'records.*.note' => 'nullable|string|max:255',
        ]);

        foreach ($data['records'] as $record) {
            if (in_array($record['status'], ['late', 'absent'], true) && trim((string) ($record['note'] ?? '')) === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Add a reason for every student who is late or absent.',
                ], 422);
            }
        }

        $sheet = $classroom->saveAttendance(
            $request->user(),
            (int) $data['class_id'],
            (int) $data['section_id'],
            $data['date'],
            $data['records']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance saved.',
            'data' => $sheet,
        ]);
    }
}
