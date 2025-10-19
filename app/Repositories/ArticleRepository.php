<?php

namespace App\Repositories;

use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ArticleRepository
{
    /**
     * Maximum articles to process in a single batch
     */
    protected const BATCH_SIZE = 100;

    /**
     * Store multiple articles with upsert (insert or update)
     * Assumes articles are already validated and deduplicated
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

  
}

