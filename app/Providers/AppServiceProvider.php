<?php

namespace App\Providers;

use App\Models\User; // <-- إضافة
use Illuminate\Support\Facades\Gate; // <-- إضافة
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('is-admin', function (User $user) {
            return $user->user_type === 'admin';
        });
    }
}