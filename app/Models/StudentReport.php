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
        'result_status',
        'result_remarks',
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
        'issue_date' => 'datetime',
        'final_gpa' => 'decimal:2'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function getAttendancePercentageAttribute()
    {
        if ($this->total_days > 0) {
            return round(($this->attendance_days / $this->total_days) * 100, 2);
        }
        return 0;
    }

    public function getResultStatusColorAttribute()
    {
        return match ($this->result_status) {
            'PASSED WITH DISTINCTION' => 'success',
            'PASSED WITH FIRST DIVISION' => 'primary',
            'PASSED WITH SECOND DIVISION' => 'info',
            'PASSED WITH THIRD DIVISION' => 'warning',
            'FAILED' => 'danger',
            default => 'secondary'
        };
    }
}
