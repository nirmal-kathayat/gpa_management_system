<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
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

    /**
     * The one role the app itself knows about: 'admin' decides school scoping,
     * not just what is permitted, and cannot be deleted from the Roles screen.
     * Every other role is whatever an administrator has created.
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * The role shown in the UI. A user normally holds one.
     */
    public function getRoleNameAttribute(): ?string
    {
        return $this->roles->first()?->name;
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
