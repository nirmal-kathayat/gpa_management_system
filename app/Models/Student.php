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
        'symbol_number',
        'gender',
        'date_of_birth',
        'date_of_admission',
        'father_name',
        'mother_name',
        'guardian_name',
        'guardian_phone',
        'address',
        'phone',
        'email',
        'photo',
        'is_active',
        'school_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_admission' => 'date',
        'is_active' => 'boolean',
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
