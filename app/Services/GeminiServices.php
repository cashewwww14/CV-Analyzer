<?php
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    public function analyzeCv(string $cvText, ?string $jobDescription = null): array
    {
        $prompt = $this->buildCvAnalysisPrompt($cvText, $jobDescription);
        
        try {
            $response = Http::timeout(60)
                ->post($this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if (!$response->successful()) {
                throw new Exception('Gemini API request failed: ' . $response->body());
            }

            $result = $response->json();
            $generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            return $this->parseAiResponse($generatedText);
            
        } catch (Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            throw new Exception('Failed to analyze CV with AI: ' . $e->getMessage());
        }
    }

    public function compareMultipleCvs(array $cvTexts, ?string $jobDescription = null): array
    {
        $prompt = $this->buildCvComparisonPrompt($cvTexts, $jobDescription);
        
        try {
            $response = Http::timeout(90)
                ->post($this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 3048,
                    ]
                ]);

            if (!$response->successful()) {
                throw new Exception('Gemini API request failed: ' . $response->body());
            }

            $result = $response->json();
            $generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            return $this->parseComparisonResponse($generatedText);
            
        } catch (Exception $e) {
            Log::error('Gemini Comparison API Error: ' . $e->getMessage());
            throw new Exception('Failed to compare CVs with AI: ' . $e->getMessage());
        }
    }

    public function generateCourseRecommendations(array $skillGaps, string $careerGoal = ''): array
    {
        $prompt = $this->buildCourseRecommendationPrompt($skillGaps, $careerGoal);
        
        try {
            $response = Http::timeout(60)
                ->post($this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

            $result = $response->json();
            $generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            return $this->parseCourseRecommendations($generatedText);
            
        } catch (Exception $e) {
            Log::error('Gemini Course Recommendation Error: ' . $e->getMessage());
            return [];
        }
    }

    private function buildCvAnalysisPrompt(string $cvText, ?string $jobDescription): string
    {
        $basePrompt = "
Anda adalah analis CV profesional dengan pengalaman 10+ tahun dalam rekrutmen dan evaluasi kandidat. 
Tugas Anda adalah menganalisis CV secara menyeluruh dan objektif.

**INSTRUKSI ANALISIS:**
1. Evaluasi CV berdasarkan struktur, konten, pengalaman, pendidikan, dan keterampilan
2. Berikan skor 0-100 untuk setiap kategori
3. Identifikasi kekuatan dan kelemahan utama
4. Berikan rekomendasi perbaikan yang spesifik dan actionable
5. Sarankan posisi yang sesuai berdasarkan profil kandidat

**CV YANG AKAN DIANALISIS:**