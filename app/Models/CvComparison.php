<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comparison_name',
        'cv_ids',
        'comparison_results',
        'ranking',
    ];

    protected function casts(): array
    {
        return [
            'cv_ids' => 'array',
            'comparison_results' => 'array',
            'ranking' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}