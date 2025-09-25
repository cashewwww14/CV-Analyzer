<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cv extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_type',
        'extracted_text',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(CvAnalysis::class);
    }

    public function latestAnalysis()
    {
        return $this->hasOne(CvAnalysis::class)->latestOfMany();
    }
}