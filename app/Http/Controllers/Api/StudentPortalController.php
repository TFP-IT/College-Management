<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function show(Request $request, StudentPortalService $portal)
    {
        return response()->json([
            'status' => 'success',
            'data' => $portal->build($request->user()),
        ]);
    }
}
