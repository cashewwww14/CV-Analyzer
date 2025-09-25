<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobDescription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JobDescriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['index', 'show']);
    }

    public function index(): JsonResponse
    {
        $jobs = JobDescription::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($jobs);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'required_skills' => 'required|array|min:1',
            'required_skills.*' => 'string',
            'preferred_skills' => 'sometimes|array',
            'preferred_skills.*' => 'string',
            'experience_level' => 'required|in:entry,junior,mid,senior,lead',
            'department' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job = JobDescription::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return response()->json(['job' => $job, 'message' => 'Job description created'], 201);
    }

    public function show(JobDescription $jobDescription): JsonResponse
    {
        return response()->json($jobDescription);
    }

    public function update(Request $request, JobDescription $jobDescription): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'required_skills' => 'sometimes|array|min:1',
            'required_skills.*' => 'string',
            'preferred_skills' => 'sometimes|array',
            'preferred_skills.*' => 'string',
            'experience_level' => 'sometimes|in:entry,junior,mid,senior,lead',
            'department' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $jobDescription->update($request->validated());

        return response()->json(['job' => $jobDescription, 'message' => 'Job description updated']);
    }

    public function destroy(JobDescription $jobDescription): JsonResponse
    {
        $jobDescription->update(['is_active' => false]);

        return response()->json(['message' => 'Job description deactivated']);
    }
}