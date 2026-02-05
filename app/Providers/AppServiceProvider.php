<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TrainingClass;
use App\Policies\TrainingClassPolicy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    protected $policies = [
        User::class => UserPolicy::class,
        TrainingClass::class => TrainingClassPolicy::class,

    ];

    /**
     * Bootstrap any application services.
     */
        public function boot(): void
    {
        
        if (request()->getHost() && str_contains(request()->getHost(), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}
