<?php

namespace App\Providers;

use App\Models\UserCase;
use App\Models\UploadedFile;
use App\Models\UserFormScheduling;
use App\Policies\UserCasePolicy;
use App\Policies\UploadedFilePolicy;
use App\Policies\UserFormSchedulingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        UserCase::class => UserCasePolicy::class,
        UploadedFile::class => UploadedFilePolicy::class,
        UserFormScheduling::class => UserFormSchedulingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define role-based gates
        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('staff', function ($user) {
            return in_array($user->role, ['admin', 'staff']);
        });

        Gate::define('inspector', function ($user) {
            return in_array($user->role, ['admin', 'staff', 'inspector']);
        });

        Gate::define('manage-users', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('manage-settings', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('view-reports', function ($user) {
            return in_array($user->role, ['admin', 'staff']);
        });

        Gate::define('assign-inspector', function ($user) {
            return in_array($user->role, ['admin', 'staff']);
        });
    }
}
