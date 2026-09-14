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
        // The class, section and roll number at the time the card was issued.
        // A student moves on; a report card does not.
        'class',
        'section',
        'roll_number',
        'final_gpa',
        'final_grade',
        'position',
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

    /**
     * SQLite does not enforce a foreign key added by ALTER TABLE ADD COLUMN, so
     * the cascade on student_marks.student_report_id never fires there. Removing
     * the marks here covers every path - controller, seeder or console.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $report) {
            $report->marks()->delete();
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /** Marks belong to the report card, not to a student and a year. */
    public function marks()
    {
        return $this->hasMany(StudentMark::class, 'student_report_id');
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
