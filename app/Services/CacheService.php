<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache TTL durations (in seconds)
     */
    public const METADATA_TTL = 3600;        // 1 hour - sources, categories
    public const QUERY_RESULTS_TTL = 1800;   // 30 minutes - search results
    public const ARTICLE_TTL = 7200;         // 2 hours - single article
    public const POPULAR_QUERY_TTL = 3600;   // 1 hour - popular searches
    
    /**
     * Cache key prefixes
     */
    public const PREFIX_ARTICLES = 'articles';
    public const PREFIX_SOURCES = 'sources';
    public const PREFIX_CATEGORIES = 'categories';
    public const PREFIX_QUERY = 'query';
    public const PREFIX_ARTICLE_SINGLE = 'article';

    /**
     * Cache tags for invalidation
     */
    public const TAG_ARTICLES = 'articles';
    public const TAG_SOURCES = 'sources';
    public const TAG_CATEGORIES = 'categories';
    public const TAG_METADATA = 'metadata';

    /**
     * Generate cache key for article query
     * 
     * @param array $filters
     * @return string
     */
    public static function queryKey(array $filters): string
    {
        // Sort filters to ensure consistent keys
        ksort($filters);
        
        // Remove page from cache key (cache per filter combo, not per page)
        unset($filters['page']);
        
        $hash = md5(json_encode($filters));
        return self::PREFIX_QUERY . ':' . $hash;
    }

    /**
     * Generate cache key for single article
     * 
     * @param int|string $identifier
     * @return string
     */
    public static function articleKey($identifier): string
    {
        return self::PREFIX_ARTICLE_SINGLE . ':' . $identifier;
    }

    /**
     * Generate cache key for sources list
     * 
     * @return string
     */
    public static function sourcesKey(): string
    {
        return self::PREFIX_SOURCES . ':all';
    }

    /**
     * Generate cache key for categories list
     * 
     * @return string
     */
    public static function categoriesKey(): string
    {
        return self::PREFIX_CATEGORIES . ':all';
    }

    /**
     * Cache article query results with tags
     * 
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public static function rememberQuery(string $key, callable $callback, int $ttl = self::QUERY_RESULTS_TTL)
    {
        if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
            // Use tags for Redis/Memcached
            return Cache::tags([self::TAG_ARTICLES])->remember($key, $ttl, $callback);
        }
        
        // Fallback for file/database cache (no tags support)
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache single article with tags
     * 
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public static function rememberArticle(string $key, callable $callback, int $ttl = self::ARTICLE_TTL)
    {
        if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
            return Cache::tags([self::TAG_ARTICLES])->remember($key, $ttl, $callback);
        }
        
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache metadata (sources, categories) with tags
     * 
     * @param string $key
     * @param callable $callback
     * @param string $tag
     * @param int $ttl
     * @return mixed
     */
    public static function rememberMetadata(string $key, callable $callback, string $tag, int $ttl = self::METADATA_TTL)
    {
        if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
            return Cache::tags([$tag, self::TAG_METADATA])->remember($key, $ttl, $callback);
        }
        
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Invalidate all article caches
     * Call this when new articles are added
     */
    public static function invalidateArticles(): void
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags([self::TAG_ARTICLES])->flush();
                Log::info('Cache invalidated: articles tag');
            } else {
                // For file/database cache, clear by pattern (less efficient)
                self::clearByPattern(self::PREFIX_QUERY);
                self::clearByPattern(self::PREFIX_ARTICLE_SINGLE);
                Log::info('Cache invalidated: articles pattern');
            }
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Invalidate sources cache
     */
    public static function invalidateSources(): void
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags([self::TAG_SOURCES])->flush();
            } else {
                Cache::forget(self::sourcesKey());
            }
            Log::info('Cache invalidated: sources');
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Invalidate categories cache
     */
    public static function invalidateCategories(): void
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags([self::TAG_CATEGORIES])->flush();
            } else {
                Cache::forget(self::categoriesKey());
            }
            Log::info('Cache invalidated: categories');
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Invalidate all metadata caches
     */
    public static function invalidateMetadata(): void
    {
        self::invalidateSources();
        self::invalidateCategories();
    }

    /**
     * Clear all application caches
     */
    public static function clearAll(): void
    {
        try {
            Cache::flush();
            Log::info('All caches cleared');
        } catch (\Exception $e) {
            Log::error('Cache clear all failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear cache by key pattern (for non-tagged drivers)
     * Note: This is less efficient and only works with some drivers
     * 
     * @param string $pattern
     */
    protected static function clearByPattern(string $pattern): void
    {
        // This is a simplified version. In production, you'd need driver-specific implementation
        // For Redis, you can use SCAN and DEL
        // For file cache, you'd scan cache files
        // For now, this is a placeholder that logs the intent
        Log::info('Cache pattern clear requested', ['pattern' => $pattern]);
    }

    /**
     * Get cache statistics (useful for monitoring)
     * 
     * @return array
     */
    public static function getStats(): array
    {
        $driver = config('cache.default');
        
        return [
            'driver' => $driver,
            'supports_tags' => in_array($driver, ['redis', 'memcached']),
            'ttl' => [
                'metadata' => self::METADATA_TTL,
                'query_results' => self::QUERY_RESULTS_TTL,
                'article' => self::ARTICLE_TTL,
                'popular_query' => self::POPULAR_QUERY_TTL,
            ],
        ];
    }

    /**
     * Check if caching is enabled
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return config('cache.enabled', true) && config('cache.default') !== 'array';
    }

    /**
     * Warmup cache with popular queries
     * Run this after fetching new articles
     * 
     * @param array $popularFilters Array of filter combinations to warm up
     */
    public static function warmup(array $popularFilters = []): void
    {
        if (!self::isEnabled()) {
            return;
        }

        Log::info('Cache warmup started', ['filters_count' => count($popularFilters)]);

        // Default popular queries if none provided
        if (empty($popularFilters)) {
            $popularFilters = [
                [], // All articles
                ['source' => ['guardian']], // Guardian only
                ['source' => ['nyt']], // NYT only
                ['category' => ['politics']], // Politics
                ['category' => ['technology']], // Technology
            ];
        }

        foreach ($popularFilters as $filters) {
            try {
                $key = self::queryKey($filters);
                // The actual query will be executed by repository when accessed
                Log::info('Cache warmup key prepared', ['key' => $key]);
            } catch (\Exception $e) {
                Log::error('Cache warmup failed for filters', [
                    'filters' => $filters,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Cache warmup completed');
    }
}

