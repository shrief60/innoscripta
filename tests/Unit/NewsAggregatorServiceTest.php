<?php

namespace Tests\Unit;

use App\Contracts\NewsSourceInterface;
use App\DTO\ArticleDTO;
use App\Services\ArticleService;
use App\Services\NewsAggregatorService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class NewsAggregatorServiceTest extends TestCase
{
    protected NewsAggregatorService $service;
    protected ArticleService|Mockery\MockInterface $articleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->articleService = Mockery::mock(ArticleService::class);
        
        // Mock Log facade
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();
    }

    /**
     * Test fetching from all sources successfully
     */
    public function test_fetch_and_store_all_sources(): void
    {
        // Arrange
        $guardianSource = $this->createMockSource('Guardian', 5);
        $nytSource = $this->createMockSource('NYT', 3);
        
        $sources = [
            'guardian' => $guardianSource,
            'nyt' => $nytSource,
        ];

        $this->articleService->shouldReceive('processAndStore')
            ->twice()
            ->andReturn([
                'inserted' => 5,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
            ], [
                'inserted' => 3,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
            ]);

        $service = new NewsAggregatorService($sources, $this->articleService);

        // Act
        $result = $service->fetchAndStoreAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('guardian', $result);
        $this->assertArrayHasKey('nyt', $result);
        
        // Check guardian results
        $this->assertTrue($result['guardian']['success']);
        $this->assertEquals(5, $result['guardian']['fetched']);
        $this->assertEquals(5, $result['guardian']['inserted']);
        
        // Check NYT results
        $this->assertTrue($result['nyt']['success']);
        $this->assertEquals(3, $result['nyt']['fetched']);
        $this->assertEquals(3, $result['nyt']['inserted']);
    }

    /**
     * Test fetching from single source successfully
     */
    public function test_fetch_from_source_successfully(): void
    {
        // Arrange
        $mockSource = $this->createMockSource('Guardian', 10);
        
        $this->articleService->shouldReceive('processAndStore')
            ->once()
            ->with(Mockery::on(function ($articles) {
                return is_array($articles) && count($articles) === 10;
            }))
            ->andReturn([
                'inserted' => 8,
                'updated' => 2,
                'failed' => 0,
                'skipped' => 0,
            ]);

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'guardian');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['fetched']);
        $this->assertEquals(8, $result['inserted']);
        $this->assertEquals(2, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertStringContainsString('Inserted: 8', $result['message']);
    }

    /**
     * Test fetching when source returns empty array
     */
    public function test_fetch_from_source_with_no_articles(): void
    {
        // Arrange
        $mockSource = Mockery::mock(NewsSourceInterface::class);
        $mockSource->shouldReceive('fetch')
            ->once()
            ->andReturn([]);

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'test-source');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals('No articles available', $result['message']);
        
        // ArticleService should not be called
        $this->articleService->shouldNotHaveReceived('processAndStore');
    }

    /**
     * Test fetching when source throws exception
     */
    public function test_fetch_from_source_handles_exception(): void
    {
        // Arrange
        $mockSource = Mockery::mock(NewsSourceInterface::class);
        $mockSource->shouldReceive('fetch')
            ->once()
            ->andThrow(new \Exception('API rate limit exceeded'));

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'failing-source');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals('API rate limit exceeded', $result['message']);
        
        // ArticleService should not be called
        $this->articleService->shouldNotHaveReceived('processAndStore');
    }

    /**
     * Test fetching with mixed results (inserted, updated, failed, skipped)
     */
    public function test_fetch_from_source_with_mixed_results(): void
    {
        // Arrange
        $mockSource = $this->createMockSource('Mixed Source', 20);
        
        $this->articleService->shouldReceive('processAndStore')
            ->once()
            ->andReturn([
                'inserted' => 10,
                'updated' => 5,
                'failed' => 2,
                'skipped' => 3,
            ]);

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'mixed-source');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(20, $result['fetched']);
        $this->assertEquals(10, $result['inserted']);
        $this->assertEquals(5, $result['updated']);
        $this->assertEquals(2, $result['failed']);
        $this->assertEquals(3, $result['skipped']);
        $this->assertStringContainsString('Inserted: 10', $result['message']);
        $this->assertStringContainsString('Updated: 5', $result['message']);
        $this->assertStringContainsString('Failed: 2', $result['message']);
        $this->assertStringContainsString('Skipped: 3', $result['message']);
    }

    /**
     * Test that DTOs are correctly converted to arrays
     */
    public function test_dtos_are_converted_to_arrays(): void
    {
        // Arrange
        $dtos = [
            $this->createMockDTO([
                'merchant_id' => 'abc123',
                'title' => 'Test Article 1',
                'slug' => 'test-article-1',
                'url' => 'https://example.com/1',
                'source_id' => 1,
            ]),
            $this->createMockDTO([
                'merchant_id' => 'def456',
                'title' => 'Test Article 2',
                'slug' => 'test-article-2',
                'url' => 'https://example.com/2',
                'source_id' => 1,
            ]),
        ];

        $mockSource = Mockery::mock(NewsSourceInterface::class);
        $mockSource->shouldReceive('fetch')
            ->once()
            ->andReturn($dtos);

        $this->articleService->shouldReceive('processAndStore')
            ->once()
            ->with(Mockery::on(function ($articles) {
                // Verify all DTOs were converted to arrays
                return is_array($articles) 
                    && count($articles) === 2
                    && isset($articles[0]['merchant_id'])
                    && $articles[0]['merchant_id'] === 'abc123'
                    && $articles[1]['merchant_id'] === 'def456';
            }))
            ->andReturn([
                'inserted' => 2,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
            ]);

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'test-source');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['inserted']);
    }

    /**
     * Test fetching from multiple sources with one failing
     */
    public function test_fetch_all_continues_when_one_source_fails(): void
    {
        // Arrange
        $successSource = $this->createMockSource('Success Source', 5);
        
        $failingSource = Mockery::mock(NewsSourceInterface::class);
        $failingSource->shouldReceive('fetch')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        $sources = [
            'success' => $successSource,
            'failing' => $failingSource,
        ];

        $this->articleService->shouldReceive('processAndStore')
            ->once() // Only called for successful source
            ->andReturn([
                'inserted' => 5,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
            ]);

        $service = new NewsAggregatorService($sources, $this->articleService);

        // Act
        $result = $service->fetchAndStoreAll();

        // Assert
        $this->assertCount(2, $result);
        
        // Successful source
        $this->assertTrue($result['success']['success']);
        $this->assertEquals(5, $result['success']['inserted']);
        
        // Failing source
        $this->assertFalse($result['failing']['success']);
        $this->assertEquals('Connection timeout', $result['failing']['message']);
    }

    /**
     * Test response structure contains all required fields
     */
    public function test_response_structure_contains_all_required_fields(): void
    {
        // Arrange
        $mockSource = $this->createMockSource('Test', 1);
        
        $this->articleService->shouldReceive('processAndStore')
            ->once()
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
            ]);

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'test');

        // Assert - Check all required keys exist
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('inserted', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test error response structure on exception
     */
    public function test_error_response_structure_on_exception(): void
    {
        // Arrange
        $mockSource = Mockery::mock(NewsSourceInterface::class);
        $mockSource->shouldReceive('fetch')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'error-source');

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('inserted', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('message', $result);
        
        // All counts should be 0 on error
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
    }

    /**
     * Test fetching with no sources configured
     */
    public function test_fetch_and_store_all_with_no_sources(): void
    {
        // Arrange
        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchAndStoreAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        // ArticleService should not be called
        $this->articleService->shouldNotHaveReceived('processAndStore');
    }

    /**
     * Test that skipped count defaults to 0 if not provided
     */
    public function test_skipped_count_defaults_to_zero(): void
    {
        // Arrange
        $mockSource = $this->createMockSource('Test', 1);
        
        // Return result without skipped key
        $this->articleService->shouldReceive('processAndStore')
            ->once()
            ->andReturn([
                'inserted' => 1,
                'updated' => 0,
                'failed' => 0,
                // skipped key intentionally omitted
            ]);

        $service = new NewsAggregatorService([], $this->articleService);

        // Act
        $result = $service->fetchFromSource($mockSource, 'test');

        // Assert
        $this->assertEquals(0, $result['skipped']);
    }


    // ==================== Helper Methods ====================

    /**
     * Create a mock NewsSourceInterface that returns DTOs
     */
    protected function createMockSource(string $sourceName, int $articleCount): NewsSourceInterface|Mockery\MockInterface
    {
        $dtos = [];
        for ($i = 1; $i <= $articleCount; $i++) {
            $dtos[] = $this->createMockDTO([
                'merchant_id' => "merchant_{$i}",
                'title' => "{$sourceName} Article {$i}",
                'slug' => strtolower("{$sourceName}-article-{$i}"),
                'url' => "https://example.com/{$i}",
                'source_id' => 1,
            ]);
        }

        $mockSource = Mockery::mock(NewsSourceInterface::class);
        $mockSource->shouldReceive('fetch')
            ->once()
            ->andReturn($dtos);

        return $mockSource;
    }

    /**
     * Create a mock ArticleDTO
     */
    protected function createMockDTO(array $data): ArticleDTO|Mockery\MockInterface
    {
        $dto = Mockery::mock(ArticleDTO::class);
        $dto->shouldReceive('toArray')
            ->andReturn($data);

        return $dto;
    }
}

