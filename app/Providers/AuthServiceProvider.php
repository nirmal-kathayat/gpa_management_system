<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define Gates for role-based access
        Gate::define('admin-access', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('teacher-access', function ($user) {
            return $user->isAdmin() || $user->isTeacher();
        });

        Gate::define('manage-school', function ($user, $schoolId = null) {
            return $user->canManageSchool($schoolId);
        });
    }
}
