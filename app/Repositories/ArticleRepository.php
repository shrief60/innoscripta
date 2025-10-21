<?php

namespace App\Repositories;

use App\Contracts\ArticleRepositoryInterface;
use App\Models\Article;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ArticleRepository implements ArticleRepositoryInterface
{
    /**
     * Maximum articles to process in a single batch
     */
    protected const BATCH_SIZE = 100;

    /**
     * Store multiple articles with upsert (insert or update)
     * 
     * @param array $articles Array of validated article data
     * @return array ['inserted' => int, 'updated' => int, 'failed' => int, 'errors' => array]
     */
    public function storeMany(array $articles): array
    {
        if (empty($articles)) {
            return ['inserted' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        }

        return $this->upsertInBatches($articles);
    }

    /**
     * Upsert articles in manageable batches
     */
    protected function upsertInBatches(array $articles): array
    {
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $chunks = array_chunk($articles, self::BATCH_SIZE);

        foreach ($chunks as $index => $chunk) {
            try {
                DB::beginTransaction();

                // Count existing articles to calculate inserts vs updates
                $existingIds = Article::whereIn('merchant_id', array_column($chunk, 'merchant_id'))
                    ->pluck('merchant_id')
                    ->toArray();

                $updateCount = count($existingIds);
                $insertCount = count($chunk) - $updateCount;

                // Fields to update (excluding unique identifier)
                $updateFields = [
                    'title',
                    'slug',
                    'description',
                    'content',
                    'source_id',
                    'author',
                    'category_id',
                    'url',
                    'thumbnail',
                    'published_at',
                    'fetched_at',
                ];

                // Perform upsert
                $affected = Article::upsert(
                    $chunk,
                    ['merchant_id'], // Only merchant_id as unique identifier
                    $updateFields
                );

                DB::commit();

                $inserted += $insertCount;
                $updated += $updateCount;

                Log::info("Batch {$index} processed", [
                    'inserted' => $insertCount,
                    'updated' => $updateCount,
                    'total' => count($chunk)
                ]);

            } catch (QueryException $e) {
                DB::rollBack();
                $failed += count($chunk);
                $errors[] = [
                    'batch' => $index,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ];

                Log::error("Batch {$index} failed", [
                    'error' => $e->getMessage(),
                    'articles' => count($chunk)
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                $failed += count($chunk);
                $errors[] = [
                    'batch' => $index,
                    'error' => $e->getMessage()
                ];

                Log::error("Unexpected error in batch {$index}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
            'total' => count($articles)
        ];
    }

    /**
     * Search and filter articles with pagination and caching
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $filters = [])
    {
        // Skip caching if disabled or in testing
        if (!CacheService::isEnabled()) {
            return $this->executeSearch($filters);
        }

        // Generate cache key (excludes page number for efficiency)
        $cacheKey = CacheService::queryKey($filters);
        
        // For paginated results, we cache each page separately
        $page = $filters['page'] ?? 1;
        $fullCacheKey = $cacheKey . ':page:' . $page;

        // Cache the query results
        return CacheService::rememberQuery($fullCacheKey, function () use ($filters) {
            return $this->executeSearch($filters);
        });
    }

    /**
     * Execute the actual search query (separated for caching)
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function executeSearch(array $filters)
    {
        $query = Article::query()->with(['source', 'category']);

        $this->applySearchFilter($query, $filters);
        $this->applySourceFilter($query, $filters);
        $this->applyCategoryFilter($query, $filters);
        $this->applyAuthorFilter($query, $filters);
        $this->applyPreferredAuthorsFilter($query, $filters);
        $this->applyDateRangeFilter($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate($filters['per_page']);
    }

    /**
     * Apply full-text search filter using query scope
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applySearchFilter($query, array $filters): void
    {
        if (!empty($filters['searchTerm'])) {
            $query->search($filters['searchTerm']);
        }
    }

    /**
     * Apply source filter using query scope
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applySourceFilter($query, array $filters): void
    {
        if (!empty($filters['source'])) {
            $query->bySource($filters['source']);
        }
    }

    /**
     * Apply category filter using query scope
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyCategoryFilter($query, array $filters): void
    {
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }
    }

    /**
     * Apply author filter using query scope
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyAuthorFilter($query, array $filters): void
    {
        if (!empty($filters['author'])) {
            $query->byAuthor($filters['author']);
        }
    }

    /**
     * Apply preferred authors filter (for personalized feed)
     * Matches articles by any of the user's preferred authors
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyPreferredAuthorsFilter($query, array $filters): void
    {
        if (!empty($filters['preferred_authors'])) {
            $authors = $filters['preferred_authors'];
            
            $query->where(function($q) use ($authors) {
                foreach ($authors as $author) {
                    $q->orWhere('author', 'like', "%{$author}%");
                }
            });
        }
    }

    /**
     * Apply date range filter using query scope
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyDateRangeFilter($query, array $filters): void
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if ($fromDate || $toDate) {
            $query->byDateRange($fromDate, $toDate);
        }
    }

    /**
     * Apply sorting using query scope
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort'] ?? 'published_at';
        $sortOrder = $filters['order'] ?? 'desc';

        $query->sortBy($sortBy, $sortOrder);
    }

}


