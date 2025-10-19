<?php

namespace App\Services;

use App\Repositories\ArticleRepository;
use Illuminate\Support\Facades\Log;

class ArticleService
{
    public function __construct(
        protected ArticleRepository $repository
    ) {}

    /**
     * Process and store articles with validation and deduplication
     * 
     * @param array $articles Raw article data from DTOs
     * @return array Result summary
     */
    public function processAndStore(array $articles): array
    {
        if (empty($articles)) {
            return [
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => []
            ];
        }

        // Remove duplicates within batch
        $unique = $this->removeDuplicates($articles);
        $duplicatesRemoved = count($articles) - count($unique);

        if ($duplicatesRemoved > 0) {
            Log::info("Removed {$duplicatesRemoved} duplicate articles from batch");
        }

        // Validate articles
        $validated = $this->validateArticles($unique);

        if (!empty($validated['invalid'])) {
            Log::warning('Invalid articles filtered out', [
                'count' => count($validated['invalid']),
                'reasons' => $validated['invalid']
            ]);
        }

        // Store valid articles
        $result = $this->repository->storeMany($validated['valid']);
        $result['skipped'] = count($validated['invalid']) + $duplicatesRemoved;

        return $result;
    }

    /**
     * Remove duplicate articles from the same batch (keep last occurrence)
     * 
     * @param array $articles
     * @return array
     */
    protected function removeDuplicates(array $articles): array
    {
        $unique = [];
        
        foreach ($articles as $article) {
            $merchantId = $article['merchant_id'] ?? null;
            if ($merchantId) {
                $unique[$merchantId] = $article; // Last occurrence wins
            }
        }

        return array_values($unique);
    }

    /**
     * Validate articles and separate valid from invalid
     * 
     * @param array $articles
     * @return array ['valid' => array, 'invalid' => array]
     */
    protected function validateArticles(array $articles): array
    {
        $valid = [];
        $invalid = [];

        foreach ($articles as $index => $article) {
            $errors = $this->validateArticle($article);

            if (empty($errors)) {
                $valid[] = $article;
            } else {
                $invalid[] = [
                    'index' => $index,
                    'title' => $article['title'] ?? 'unknown',
                    'merchant_id' => $article['merchant_id'] ?? 'unknown',
                    'errors' => $errors
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }

    /**
     * Validate a single article
     * 
     * @param array $article
     * @return array List of validation errors
     */
    protected function validateArticle(array $article): array
    {
        $errors = [];

        // Required fields
        if (empty($article['merchant_id'])) {
            $errors[] = 'Missing merchant_id';
        }

        if (empty($article['title'])) {
            $errors[] = 'Missing title';
        }

        if (empty($article['slug'])) {
            $errors[] = 'Missing slug';
        }

        if (empty($article['url'])) {
            $errors[] = 'Missing url';
        }

        if (empty($article['source_id'])) {
            $errors[] = 'Missing source_id';
        }

        // URL validation
        if (!empty($article['url']) && !filter_var($article['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid URL format';
        }

        // Title length validation
        if (!empty($article['title']) && strlen($article['title']) > 255) {
            $errors[] = 'Title too long (max 255 characters)';
        }

        return $errors;
    }
}

