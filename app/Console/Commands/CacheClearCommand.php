<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-app {type? : Cache type to clear (articles, sources, categories, metadata, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application caches (articles, sources, categories, or all)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type') ?? 'all';

        $this->info("Clearing {$type} cache...");

        switch ($type) {
            case 'articles':
                CacheService::invalidateArticles();
                $this->info('✓ Articles cache cleared');
                break;

            case 'sources':
                CacheService::invalidateSources();
                $this->info('✓ Sources cache cleared');
                break;

            case 'categories':
                CacheService::invalidateCategories();
                $this->info('✓ Categories cache cleared');
                break;

            case 'metadata':
                CacheService::invalidateMetadata();
                $this->info('✓ Metadata cache cleared (sources + categories)');
                break;

            case 'all':
                CacheService::clearAll();
                $this->info('✓ All caches cleared');
                break;

            default:
                $this->error("Invalid cache type: {$type}");
                $this->info('Valid types: articles, sources, categories, metadata, all');
                return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
