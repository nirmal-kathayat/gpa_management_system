<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'school_id',
        'phone',
        'address',
        'is_active',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isTeacher()
    {
        return $this->role === 'teacher';
    }

    public function isStaff()
    {
        return $this->role === 'staff';
    }

    public function canManageSchool($schoolId = null)
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($schoolId && $this->school_id) {
            return $this->school_id == $schoolId;
        }
        
        return false;
    }

    public function getAccessibleStudents()
    {
        if ($this->isAdmin()) {
            return Student::query();
        }
        
        return Student::where('school_id', $this->school_id);
    }
}
