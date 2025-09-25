<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvComparison;
use App\Services\CvScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ComparisonController extends Controller
{
    public function __construct(private CvScoringService $scoringService)
    {
        $this->middleware('auth:sanctum');
    }

    public function compare(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cv_ids' => 'required|array|min:2|max:5',
            'cv_ids.*' => 'exists:cvs,id',
            'job_description_id' => 'sometimes|exists:job_descriptions,id',
            'comparison_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Verify ownership of all CVs
            $cvs = auth()->user()->cvs()->whereIn('id', $request->cv_ids)->get();
            
            if ($cvs->count() !== count($request->cv_ids)) {
                return response()->json(['error' => 'Some CVs not found or unauthorized'], 403);
            }

            $jobDescription = $request->job_description_id ? 
                \App\Models\JobDescription::find($request->job_description_id) : null;

            $comparisonResult = $this->scoringService->compareMultipleCvs(
                $request->cv_ids,
                $jobDescription
            );

            // Save comparison
            $comparison = CvComparison::create([
                'user_id' => auth()->id(),
                'comparison_name' => $request->comparison_name,
                'cv_ids' => $request->cv_ids,
                'comparison_results' => $comparisonResult['comparison_results'],
                'ranking' => $comparisonResult['comparison_results']['ranking'] ?? [],
            ]);

            return response()->json([
                'comparison' => $comparison,
                'results' => $comparisonResult,
                'message' => 'CV comparison completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $comparisons = CvComparison::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($comparisons);
    }

    public function show($id): JsonResponse
    {
        $comparison = CvComparison::where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($comparison);
    }
}