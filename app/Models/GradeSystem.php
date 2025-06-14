<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_grade',
        'grade_point',
        'marks_from',
        'marks_to',
        'remarks'
    ];

    public static function getGradeByMarks($marks)
    {
        return self::where('marks_from', '<=', $marks)
                   ->where('marks_to', '>=', $marks)
                   ->first();
    }
}
