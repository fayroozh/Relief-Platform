<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // نلغي الواجهات الافتراضية ونربط الكونترولر تبعنا
        Fortify::loginView(function () {
            abort(404); // ما نستخدم فيو، كله API
        });

        Fortify::registerView(function () {
            abort(404);
        });
    }
}
