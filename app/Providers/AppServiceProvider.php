<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\NewsAggregatorService;
use App\Services\ArticleService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NewsAggregatorService::class, function ($app) {
            $sourceClasses = config('news.sources', []);
            $sources = [];

            foreach ($sourceClasses as $name => $class) {
                $sources[$name] = $app->make($class);
            }

            return new NewsAggregatorService(
                sources: $sources,
                articleService: $app->make(ArticleService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
