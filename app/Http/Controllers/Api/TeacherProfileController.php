<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeacherProfileService;
use Illuminate\Http\Request;

class TeacherProfileController extends Controller
{
    public function show(Request $request, TeacherProfileService $profiles)
    {
        return response()->json([
            'status' => 'success',
            'data' => $profiles->build($request->user()),
        ]);
    }

    public function updateClassStatus(Request $request, TeacherProfileService $profiles)
    {
        $data = $request->validate([
            'routine_id' => 'required|integer',
            'status' => 'required|in:in_class,online,postpone,done',
        ]);

        $profiles->setClassStatus($request->user(), (int) $data['routine_id'], $data['status']);

        return response()->json([
            'status' => 'success',
            'data' => $profiles->todayClasses($request->user()),
        ]);
    }
}
