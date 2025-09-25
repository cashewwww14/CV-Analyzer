<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\JobDescription;
use App\Services\CvScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AnalysisController extends Controller
{
    public function __construct(private CvScoringService $scoringService)
    {
        $this->middleware('auth:sanctum');
    }

    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cv_id' => 'required|exists:cvs,id',
            'job_description_id' => 'sometimes|exists:job_descriptions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cv = Cv::findOrFail($request->cv_id);
        
        // Check ownership
        if ($cv->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $jobDescription = $request->job_description_id ? 
                JobDescription::find($request->job_description_id) : null;

            $analysis = $this->scoringService->analyzeCv($cv, $jobDescription);
            
            $analysis->load(['cv', 'jobDescription']);

            return response()->json([
                'analysis' => $analysis,
                'message' => 'CV analysis completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $analyses = $request->user()
            ->cvs()
            ->with(['analyses.jobDescription'])
            ->get()
            ->pluck('analyses')
            ->flatten()
            ->sortByDesc('created_at')
            ->values();

        return response()->json($analyses);
    }

    public function show($id): JsonResponse
    {
        $analysis = \App\Models\CvAnalysis::with(['cv.user', 'jobDescription'])
            ->findOrFail($id);

        // Check ownership
        if ($analysis->cv->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($analysis);
    }

    public function generateCourseRecommendations($analysisId): JsonResponse
    {
        $analysis = \App\Models\CvAnalysis::with('cv.user')->findOrFail($analysisId);

        // Check ownership
        if ($analysis->cv->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $recommendations = $this->scoringService->generateCourseRecommendations($analysis);

            return response()->json([
                'recommendations' => $recommendations,
                'message' => 'Course recommendations generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}