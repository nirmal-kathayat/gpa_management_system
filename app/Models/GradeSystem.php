<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One band of the grading scale: a percentage range, the letter shown on the
 * report card, and the grade point it is worth.
 *
 * Reading the scale to grade something goes through App\Support\GradeCalculator
 * rather than this model, so every caller gets the same answer.
 */
class GradeSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_grade',
        'grade_point',
        'marks_from',
        'marks_to',
        'description',
        'is_failing',
        'is_active',
    ];

    protected $casts = [
        'grade_point' => 'float',
        'marks_from' => 'integer',
        'marks_to' => 'integer',
        'is_failing' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Highest band first, which is how a scale is read. */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('marks_from');
    }

    public function isPassing(): bool
    {
        return ! $this->is_failing;
    }
}
