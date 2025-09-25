<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'cv_id',
        'job_description_id',
        'overall_score',
        'detailed_scores',
        'strengths',
        'weaknesses',
        'recommendations',
        'matched_jobs',
        'skill_gaps',
        'course_recommendations',
        'ai_feedback',
    ];

    protected function casts(): array
    {
        return [
            'detailed_scores' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
            'recommendations' => 'array',
            'matched_jobs' => 'array',
            'skill_gaps' => 'array',
            'course_recommendations' => 'array',
        ];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }
}