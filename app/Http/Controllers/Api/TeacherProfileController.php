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
}
