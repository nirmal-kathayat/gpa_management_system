<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class',
        'section',
        'roll_number',
        'date_of_birth',
        'father_name',
        'mother_name',
        'address',
        'phone',
        'school_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function marks()
    {
        return $this->hasMany(StudentMark::class);
    }

    public function reports()
    {
        return $this->hasMany(StudentReport::class);
    }
}
