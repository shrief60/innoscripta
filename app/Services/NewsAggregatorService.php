<?php

namespace App\Services;

use App\Contracts\NewsSourceInterface;
use App\Services\ArticleService;
use Illuminate\Support\Facades\Log;

class NewsAggregatorService
{
    /**
     * @param array<NewsSourceInterface> $sources
     * @param ArticleService $articleService
     */
    public function __construct(
        protected array $sources,
        protected ArticleService $articleService
    ) {}

    /**
     * Fetch and store articles from all configured sources
     * 
     * @return array of fetched articles per source
     */
    public function fetchAndStoreAll(): array
    {
        $articles = [];

        foreach ($this->sources as $sourceName => $source) {
            $articles[$sourceName] = $this->fetchFromSource($source, $sourceName);
        }

        return $articles;
    }

    /**
     * Fetch and store articles from a specific source
     * 
     * @param NewsSourceInterface $source
     * @param string $sourceName
     * @return array
     */
    public function fetchFromSource(NewsSourceInterface $source, string $sourceName): array
    {
        try {
            Log::info("Fetching articles from {$sourceName}");

            $articles = $source->fetch();

            if (empty($articles)) {
                Log::warning("No articles fetched from {$sourceName}");
                return [
                    'success' => true,
                    'fetched' => 0,
                    'inserted' => 0,
                    'updated' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'message' => 'No articles available',
                ];
            }

            $articlesData = array_map(fn($dto) => $dto->toArray(), $articles);

            $result = $this->articleService->processAndStore($articlesData);

            Log::info("Successfully processed articles from {$sourceName}", $result);

            return [
                'success' => true,
                'fetched' => count($articles),
                'inserted' => $result['inserted'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'skipped' => $result['skipped'] ?? 0,
                'message' => "Inserted: {$result['inserted']}, Updated: {$result['updated']}, Failed: {$result['failed']}, Skipped: {$result['skipped']}",
            ];

        } catch (\Exception $e) {
            Log::error("Failed to fetch articles from {$sourceName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'fetched' => 0,
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

}
