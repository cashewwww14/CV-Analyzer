<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'required_skills',
        'preferred_skills',
        'experience_level',
        'department',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'preferred_skills' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(CvAnalysis::class);
    }
}