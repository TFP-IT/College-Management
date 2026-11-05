<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Http\Resources\ProgramResource;
use App\Modules\Admissions\Models\AdmissionProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramApiController extends BaseApiController
{
    /**
     * List all active admission programs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdmissionProgram::with(['academicYear', 'academicClass', 'academicGroup'])
            ->active();

        if ($request->filled('section_type')) {
            $query->where('section_type', strtoupper($request->section_type));
        }

        $programs = $query->orderBy('created_at', 'desc')->get();

        return $this->respondWithSuccess(
            ProgramResource::collection($programs),
            'Active admission programs retrieved successfully.'
        );
    }

    /**
     * Get details of a single admission program.
     */
    public function show(string $id): JsonResponse
    {
        $program = AdmissionProgram::with(['academicYear', 'academicClass', 'academicGroup'])
            ->findOrFail($id);

        return $this->respondWithSuccess(
            new ProgramResource($program),
            'Program details retrieved successfully.'
        );
    }
}

