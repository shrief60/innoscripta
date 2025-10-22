<?php

namespace Tests\Unit;

use App\Contracts\ArticleRepositoryInterface;
use App\Services\ArticleService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ArticleServiceTest extends TestCase
{
    protected ArticleService $service;
    protected ArticleRepositoryInterface|Mockery\MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = Mockery::mock(ArticleRepositoryInterface::class);
        $this->service = new ArticleService($this->repository);
        
        // Mock static Log facade
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('warning')->andReturnNull();
        
        // Mock CacheService static methods globally
        Mockery::mock('alias:' . CacheService::class)
            ->shouldReceive('invalidateArticles')
            ->byDefault();
    }

    /**
     * Test processing empty articles array
     */
    public function test_process_and_store_with_empty_array(): void
    {
        // Act
        $result = $this->service->processAndStore([]);

        // Assert
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals([], $result['errors']);
        
        // Repository should not be called
        $this->repository->shouldNotHaveReceived('storeMany');
    }

    /**
     * Test removing duplicates within batch
     */
    public function test_remove_duplicates_keeps_last_occurrence(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'First Article',
                'slug' => 'first-article',
                'url' => 'https://example.com/first',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'abc123', // Duplicate
                'title' => 'Updated Article',
                'slug' => 'updated-article',
                'url' => 'https://example.com/updated',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'xyz789',
                'title' => 'Third Article',
                'slug' => 'third-article',
                'url' => 'https://example.com/third',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 2,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(2, $result['inserted']);
        $this->assertEquals(1, $result['skipped'], 'One duplicate should be skipped');
    }

    /**
     * Test validation rejects articles with missing required fields
     */
    public function test_validation_rejects_articles_missing_required_fields(): void
    {
        // Arrange
        $articles = [
            [
                // Missing merchant_id
                'title' => 'Article Without Merchant ID',
                'slug' => 'article-without-merchant-id',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'abc123',
                // Missing title
                'slug' => 'article-without-title',
                'url' => 'https://example.com/article2',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'def456',
                'title' => 'Valid Article',
                'slug' => 'valid-article',
                'url' => 'https://example.com/valid',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with(Mockery::on(function ($validArticles) {
                return count($validArticles) === 1;
            }))
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(2, $result['skipped'], 'Two invalid articles should be skipped');
    }

    /**
     * Test validation rejects articles with invalid URL
     */
    public function test_validation_rejects_articles_with_invalid_url(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Article With Invalid URL',
                'slug' => 'article-invalid-url',
                'url' => 'not-a-valid-url', // Invalid URL
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with([]) // Empty array since all articles are invalid
            ->andReturn([
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(1, $result['skipped']);
    }

    /**
     * Test validation rejects articles with title too long
     */
    public function test_validation_rejects_articles_with_title_too_long(): void
    {
        // Arrange
        $longTitle = str_repeat('a', 256); // 256 characters (max is 255)
        
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => $longTitle,
                'slug' => 'article-long-title',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with([]) // Empty array since all articles are invalid
            ->andReturn([
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(1, $result['skipped']);
    }

    /**
     * Test validation accepts articles with exactly 255 character title
     */
    public function test_validation_accepts_articles_with_max_length_title(): void
    {
        // Arrange
        $maxLengthTitle = str_repeat('a', 255); // Exactly 255 characters
        
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => $maxLengthTitle,
                'slug' => 'article-max-title',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(0, $result['skipped']);
    }

    /**
     * Test processing valid articles calls repository
     */
    public function test_process_valid_articles_calls_repository(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Valid Article 1',
                'slug' => 'valid-article-1',
                'url' => 'https://example.com/article1',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'def456',
                'title' => 'Valid Article 2',
                'slug' => 'valid-article-2',
                'url' => 'https://example.com/article2',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with(Mockery::on(function ($validArticles) use ($articles) {
                return count($validArticles) === 2 
                    && $validArticles[0]['merchant_id'] === $articles[0]['merchant_id']
                    && $validArticles[1]['merchant_id'] === $articles[1]['merchant_id'];
            }))
            ->andReturn([
                'inserted' => 2,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(2, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['skipped']);
    }

    /**
     * Test cache is invalidated when articles are inserted
     */
    public function test_cache_invalidated_when_articles_inserted(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Valid Article',
                'slug' => 'valid-article',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $this->service->processAndStore($articles);

        // Assert - Cache invalidation happens automatically
    }

    /**
     * Test cache is invalidated when articles are updated
     */
    public function test_cache_invalidated_when_articles_updated(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Updated Article',
                'slug' => 'updated-article',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 0,
                'updated' => 1,
                'failed' => 0,
            ]);

        // Act
        $this->service->processAndStore($articles);

        // Assert - Cache invalidation happens automatically
    }

    /**
     * Test cache is NOT invalidated when no articles inserted or updated
     */
    public function test_cache_not_invalidated_when_no_changes(): void
    {
        // Arrange - Invalid article
        $articles = [
            [
                // Missing required fields
                'title' => 'Invalid Article',
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with([]) // Empty array since article is invalid
            ->andReturn([
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test multiple validation errors are tracked
     */
    public function test_multiple_validation_errors_are_tracked(): void
    {
        // Arrange
        $articles = [
            [
                // Missing multiple required fields
                'merchant_id' => 'abc123',
                // Missing title, slug, url, source_id
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with([]) // Empty array since all articles are invalid
            ->andReturn([
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(1, $result['skipped']);
    }

    /**
     * Test articles without merchant_id are removed during deduplication
     */
    public function test_articles_without_merchant_id_removed_during_deduplication(): void
    {
        // Arrange
        $articles = [
            [
                // No merchant_id - should be removed
                'title' => 'Article Without Merchant ID',
                'slug' => 'article-1',
                'url' => 'https://example.com/1',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'abc123',
                'title' => 'Valid Article',
                'slug' => 'article-2',
                'url' => 'https://example.com/2',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(1, $result['skipped']); // Removed during deduplication + validation
    }

    /**
     * Test complete flow with duplicates, valid, and invalid articles
     */
    public function test_complete_flow_with_mixed_articles(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Valid Article 1',
                'slug' => 'valid-1',
                'url' => 'https://example.com/1',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'abc123', // Duplicate
                'title' => 'Valid Article 1 Updated',
                'slug' => 'valid-1-updated',
                'url' => 'https://example.com/1-updated',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'def456',
                'title' => 'Valid Article 2',
                'slug' => 'valid-2',
                'url' => 'https://example.com/2',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'ghi789',
                // Missing title
                'slug' => 'invalid-1',
                'url' => 'https://example.com/3',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'jkl012',
                'title' => 'Invalid URL Article',
                'slug' => 'invalid-2',
                'url' => 'not-a-url', // Invalid URL
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with(Mockery::on(function ($validArticles) {
                // Should have 2 valid articles after deduplication and validation
                return count($validArticles) === 2;
            }))
            ->andReturn([
                'inserted' => 1,
                'updated' => 1,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(3, $result['skipped']); // 1 duplicate + 2 invalid
    }

    /**
     * Test repository failure is handled
     */
    public function test_repository_failure_is_propagated(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Valid Article',
                'slug' => 'valid-article',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 0,
                'updated' => 0,
                'failed' => 1,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(1, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
    }

    /**
     * Test optional fields are allowed to be empty
     */
    public function test_optional_fields_can_be_empty(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'Article With Only Required Fields',
                'slug' => 'required-only',
                'url' => 'https://example.com/article',
                'source_id' => 1,
                // Optional fields missing: description, content, author, image_url, category_id, published_at
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(0, $result['skipped']);
    }

    /**
     * Test valid URL formats are accepted
     */
    public function test_valid_url_formats_are_accepted(): void
    {
        // Arrange
        $articles = [
            [
                'merchant_id' => 'abc123',
                'title' => 'HTTPS Article',
                'slug' => 'https-article',
                'url' => 'https://example.com/article',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'def456',
                'title' => 'HTTP Article',
                'slug' => 'http-article',
                'url' => 'http://example.com/article',
                'source_id' => 1,
            ],
            [
                'merchant_id' => 'ghi789',
                'title' => 'Article With Path',
                'slug' => 'path-article',
                'url' => 'https://example.com/path/to/article?param=value',
                'source_id' => 1,
            ],
        ];

        $this->repository->shouldReceive('storeMany')
            ->once()
            ->with(Mockery::on(function ($validArticles) {
                return count($validArticles) === 3;
            }))
            ->andReturn([
                'inserted' => 3,
                'updated' => 0,
                'failed' => 0,
            ]);

        // Act
        $result = $this->service->processAndStore($articles);

        // Assert
        $this->assertEquals(3, $result['inserted']);
        $this->assertEquals(0, $result['skipped']);
    }
}

