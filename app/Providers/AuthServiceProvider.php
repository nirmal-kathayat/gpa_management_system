<?php

namespace App\Providers;

use App\Models\User;
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

        // Admins pass every permission check, so a newly added permission never
        // locks the person who has to grant it out of the screen that grants it.
        // Returning null (not false) lets every other check run normally.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // School access is a data-ownership question, not a permission, so it
        // stays a gate of its own alongside Controller::authorizeSchool().
        Gate::define('manage-school', function (User $user, $schoolId = null) {
            return $user->canManageSchool($schoolId);
        });
    }
}
