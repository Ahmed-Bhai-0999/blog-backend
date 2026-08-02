<?php

namespace App\Providers;

use App\Support\Media\ModelPathGenerator;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PathGenerator::class,
            ModelPathGenerator::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Comment::observe(\App\Observers\CommentObserver::class);
    }
}
