<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'tagline',
        'established',
        'type',
        'about',
        'address',
        'phone',
        'email',
        'logo'
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class)->ordered();
    }

    public function reports()
    {
        return $this->hasManyThrough(StudentReport::class, Student::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
