<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\NewsAggregatorService;
use App\Services\ArticleService;
use App\Contracts\ArticleRepositoryInterface;
use App\Repositories\ArticleRepository;
use App\Contracts\PersonalizedFeedServiceInterface;
use App\Services\PersonalizedFeedService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Repository Interfaces to Concrete Implementations
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        
        // Bind Service Interfaces to Concrete Implementations
        $this->app->bind(PersonalizedFeedServiceInterface::class, PersonalizedFeedService::class);

        // Register NewsAggregatorService as singleton
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
