<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'final_gpa',
        'final_grade',
        'position',
        'attendance_days',
        'total_days',
        'remarks',
        'class_response',
        'discipline',
        'leadership',
        'neatness',
        'punctuality',
        'regularity',
        'social_conduct',
        'sports_game',
        'issue_date'
    ];

    protected $casts = [
        'issue_date' => 'date'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
