<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Services\OcrService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CvController extends Controller
{
    public function __construct(private OcrService $ocrService)
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $cvs = Cv::where('user_id', $request->user()->id)
            ->with(['latestAnalysis'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($cvs);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cv_file' => 'required|file|mimes:pdf,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('cv_file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $storedName = Str::uuid() . '.' . $extension;
            
            // Store file
            $filePath = $file->storeAs('cvs', $storedName, 'local');
            
            // Extract text
            $extractedText = $this->ocrService->extractTextFromFile(
                storage_path('app/' . $filePath),
                $extension
            );

            // Extract metadata
            $metadata = $this->ocrService->extractMetadata(
                storage_path('app/' . $filePath),
                $extension
            );

            // Create CV record
            $cv = Cv::create([
                'user_id' => $request->user()->id,
                'original_filename' => $originalName,
                'stored_filename' => $storedName,
                'file_path' => $filePath,
                'file_type' => $extension,
                'extracted_text' => $extractedText,
                'metadata' => $metadata,
            ]);

            return response()->json([
                'cv' => $cv,
                'message' => 'CV uploaded successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process CV: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Cv $cv): JsonResponse
    {
        // Check ownership
        if ($cv->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cv->load(['analyses.jobDescription']);
        
        return response()->json($cv);
    }

    public function destroy(Cv $cv): JsonResponse
    {
        // Check ownership
        if ($cv->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Delete file from storage
            Storage::delete($cv->file_path);
            
            // Delete record
            $cv->delete();

            return response()->json(['message' => 'CV deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete CV'], 500);
        }
    }

    public function download(Cv $cv): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Check ownership
        if ($cv->user_id !== auth()->id()) {
            abort(403);
        }

        $filePath = storage_path('app/' . $cv->file_path);
        
        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath, $cv->original_filename);
    }
}