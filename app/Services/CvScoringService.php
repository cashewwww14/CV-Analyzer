<?php
namespace App\Services;

use App\Models\Cv;
use App\Models\CvAnalysis;
use App\Models\JobDescription;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CvScoringService
{
    public function __construct(
        private GeminiService $geminiService,
        private OcrService $ocrService
    ) {}

    public function analyzeCv(Cv $cv, ?JobDescription $jobDescription = null): CvAnalysis
    {
        DB::beginTransaction();
        
        try {
            // Extract text if not already done
            if (empty($cv->extracted_text)) {
                $cv->extracted_text = $this->ocrService->extractTextFromFile(
                    storage_path('app/' . $cv->file_path),
                    $cv->file_type
                );
                $cv->save();
            }

            // Prepare job description text
            $jobDescText = $jobDescription ? 
                "Position: {$jobDescription->title}\n" .
                "Description: {$jobDescription->description}\n" .
                "Required Skills: " . implode(', ', $jobDescription->required_skills) :
                null;

            // Analyze with AI
            $aiResult = $this->geminiService->analyzeCv($cv->extracted_text, $jobDescText);

            // Create analysis record
            $analysis = CvAnalysis::create([
                'cv_id' => $cv->id,
                'job_description_id' => $jobDescription?->id,
                'overall_score' => $aiResult['overall_score'],
                'detailed_scores' => $aiResult['detailed_scores'],
                'strengths' => $aiResult['strengths'],
                'weaknesses' => $aiResult['weaknesses'],
                'recommendations' => $aiResult['recommendations'],
                'matched_jobs' => $aiResult['matched_jobs'] ?? [],
                'skill_gaps' => $aiResult['skill_gaps'] ?? [],
                'course_recommendations' => $aiResult['course_recommendations'] ?? [],
                'ai_feedback' => $aiResult['feedback'],
            ]);

            DB::commit();
            return $analysis;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("CV Analysis failed for CV {$cv->id}: " . $e->getMessage());
            throw new Exception("Analysis failed: " . $e->getMessage());
        }
    }

    public function compareMultipleCvs(array $cvIds, ?JobDescription $jobDescription = null): array
    {
        try {
            $cvs = Cv::whereIn('id', $cvIds)->with('user')->get();
            
            if ($cvs->count() !== count($cvIds)) {
                throw new Exception('Some CVs not found');
            }

            $cvTexts = [];
            foreach ($cvs as $cv) {
                if (empty($cv->extracted_text)) {
                    $cv->extracted_text = $this->ocrService->extractTextFromFile(
                        storage_path('app/' . $cv->file_path),
                        $cv->file_type
                    );
                    $cv->save();
                }
                $cvTexts[] = $cv->extracted_text;
            }

            $jobDescText = $jobDescription ?
                "Position: {$jobDescription->title}\nDescription: {$jobDescription->description}" :
                null;

            $comparisonResult = $this->geminiService->compareMultipleCvs($cvTexts, $jobDescText);

            return [
                'cvs' => $cvs,
                'comparison_results' => $comparisonResult,
                'job_description' => $jobDescription,
            ];

        } catch (Exception $e) {
            Log::error("CV Comparison failed: " . $e->getMessage());
            throw new Exception("Comparison failed: " . $e->getMessage());
        }
    }

    public function generateCourseRecommendations(CvAnalysis $analysis): array
    {
        try {
            $skillGaps = $analysis->skill_gaps ?? [];
            $careerGoal = "Based on CV analysis for improvement";

            return $this->geminiService->generateCourseRecommendations($skillGaps, $careerGoal);

        } catch (Exception $e) {
            Log::error("Course recommendation generation failed: " . $e->getMessage());
            return [];
        }
    }

    public function getAnalyticsData(): array
    {
        try {
            return [
                'total_cvs' => Cv::count(),
                'total_analyses' => CvAnalysis::count(),
                'average_score' => CvAnalysis::avg('overall_score'),
                'score_distribution' => $this->getScoreDistribution(),
                'top_skills_gaps' => $this->getTopSkillGaps(),
                'monthly_uploads' => $this->getMonthlyUploads(),
                'job_matching_stats' => $this->getJobMatchingStats(),
            ];
        } catch (Exception $e) {
            Log::error("Analytics data generation failed: " . $e->getMessage());
            return [];
        }
    }

    private function getScoreDistribution(): array
    {
        return CvAnalysis::selectRaw("
            CASE 
                WHEN overall_score >= 90 THEN 'Excellent (90-100)'
                WHEN overall_score >= 80 THEN 'Good (80-89)'
                WHEN overall_score >= 70 THEN 'Average (70-79)'
                WHEN overall_score >= 60 THEN 'Below Average (60-69)'
                ELSE 'Poor (0-59)'
            END as score_range,
            COUNT(*) as count
        ")
        ->groupBy('score_range')
        ->pluck('count', 'score_range')
        ->toArray();
    }

    private function getTopSkillGaps(): array
    {
        $skillGaps = CvAnalysis::whereNotNull('skill_gaps')
            ->pluck('skill_gaps')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10);

        return $skillGaps->toArray();
    }

    private function getMonthlyUploads(): array
    {
        return Cv::selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        ")
        ->where('created_at', '>=', now()->subMonths(12))
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('count', 'month')
        ->toArray();
    }

    private function getJobMatchingStats(): array
    {
        return [
            'with_job_description' => CvAnalysis::whereNotNull('job_description_id')->count(),
            'without_job_description' => CvAnalysis::whereNull('job_description_id')->count(),
        ];
    }
}