<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserPreference;
use App\Services\UserPreferenceFilterBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Mockery;

class UserPreferenceFilterBuilderTest extends TestCase
{
    protected UserPreferenceFilterBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new UserPreferenceFilterBuilder();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test building filters from user preferences only (no request)
     */
    public function test_build_filters_from_preferences_only(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 20,
            'preferred_authors' => ['John Doe', 'Jane Smith'],
        ]);

        $sources = $this->createMockSources(['guardian', 'nyt']);
        $categories = $this->createMockCategories(['technology', 'business']);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        // Act
        $filters = $this->builder->build($user);

        // Assert
        $this->assertEquals(['guardian', 'nyt'], $filters['source']);
        $this->assertEquals(['technology', 'business'], $filters['category']);
        $this->assertEquals(['John Doe', 'Jane Smith'], $filters['preferred_authors']);
        $this->assertEquals('published_at', $filters['sort']);
        $this->assertEquals('desc', $filters['order']);
        $this->assertEquals(20, $filters['per_page']);
    }

    /**
     * Test building filters with empty preferences
     */
    public function test_build_filters_with_empty_preferences(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => null,
        ]);

        $emptySources = collect([]);
        $emptyCategories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($emptySources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($emptyCategories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        // Act
        $filters = $this->builder->build($user);

        // Assert
        $this->assertArrayNotHasKey('source', $filters);
        $this->assertArrayNotHasKey('category', $filters);
        $this->assertArrayNotHasKey('preferred_authors', $filters);
        $this->assertEquals('published_at', $filters['sort']);
        $this->assertEquals('desc', $filters['order']);
        $this->assertEquals(15, $filters['per_page']);
    }

    /**
     * Test overriding preferences with request parameters
     */
    public function test_override_preferences_with_request_parameters(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 20,
            'preferred_authors' => [],
        ]);

        $sources = $this->createMockSources(['guardian']);
        $categories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'sort' => 'title',
            'order' => 'asc',
            'per_page' => 50,
            'page' => 2,
            'searchTerm' => 'climate change',
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        $this->assertEquals('title', $filters['sort'], 'Sort should be overridden');
        $this->assertEquals('asc', $filters['order'], 'Order should be overridden');
        $this->assertEquals(50, $filters['per_page'], 'Per page should be overridden');
        $this->assertEquals(2, $filters['page'], 'Page should be added');
        $this->assertEquals('climate change', $filters['searchTerm'], 'Search term should be added');
        $this->assertEquals(['guardian'], $filters['source'], 'Sources should remain from preferences');
    }

    /**
     * Test expanding source filters by merging request with preferences
     */
    public function test_expand_source_filters_from_request(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => [],
        ]);

        $sources = $this->createMockSources(['guardian']);
        $categories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'source' => ['nyt', 'newsapi'],
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        $this->assertCount(3, $filters['source']);
        $this->assertContains('guardian', $filters['source'], 'Should contain preference source');
        $this->assertContains('nyt', $filters['source'], 'Should contain request source 1');
        $this->assertContains('newsapi', $filters['source'], 'Should contain request source 2');
    }

    /**
     * Test expanding category filters by merging request with preferences
     */
    public function test_expand_category_filters_from_request(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => [],
        ]);

        $sources = collect([]);
        $categories = $this->createMockCategories(['technology']);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'category' => ['business', 'politics'],
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        $this->assertCount(3, $filters['category']);
        $this->assertContains('technology', $filters['category'], 'Should contain preference category');
        $this->assertContains('business', $filters['category'], 'Should contain request category 1');
        $this->assertContains('politics', $filters['category'], 'Should contain request category 2');
    }

    /**
     * Test that duplicate sources are removed when merging
     */
    public function test_removes_duplicate_sources_when_merging(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => [],
        ]);

        $sources = $this->createMockSources(['guardian', 'nyt']);
        $categories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'source' => ['guardian', 'newsapi'], // guardian is duplicate
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        $this->assertCount(3, $filters['source'], 'Should have 3 unique sources');
        $this->assertContains('guardian', $filters['source']);
        $this->assertContains('nyt', $filters['source']);
        $this->assertContains('newsapi', $filters['source']);
    }

    /**
     * Test normalizing single value input to array
     */
    public function test_normalize_single_source_to_array(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => [],
        ]);

        $sources = collect([]);
        $categories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'source' => 'guardian', // Single string instead of array
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        $this->assertIsArray($filters['source']);
        $this->assertCount(1, $filters['source']);
        $this->assertContains('guardian', $filters['source']);
    }

    /**
     * Test adding date range filters from request
     */
    public function test_add_date_range_filters_from_request(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => [],
        ]);

        $sources = collect([]);
        $categories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        $this->assertEquals('2024-01-01', $filters['from_date']);
        $this->assertEquals('2024-12-31', $filters['to_date']);
    }

    /**
     * Test complete scenario with all filter types
     */
    public function test_build_with_all_filter_types(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 20,
            'preferred_authors' => ['John Doe'],
        ]);

        $sources = $this->createMockSources(['guardian']);
        $categories = $this->createMockCategories(['technology']);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        $request = Request::create('/test', 'GET', [
            'source' => ['nyt'],
            'category' => ['business'],
            'searchTerm' => 'technology news',
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'sort' => 'title',
            'order' => 'asc',
            'per_page' => 50,
            'page' => 3,
        ]);

        // Act
        $filters = $this->builder->build($user, $request);

        // Assert
        // Merged sources
        $this->assertCount(2, $filters['source']);
        $this->assertContains('guardian', $filters['source']);
        $this->assertContains('nyt', $filters['source']);

        // Merged categories
        $this->assertCount(2, $filters['category']);
        $this->assertContains('technology', $filters['category']);
        $this->assertContains('business', $filters['category']);

        // Preferred authors from preferences
        $this->assertEquals(['John Doe'], $filters['preferred_authors']);

        // Overridden values from request
        $this->assertEquals('technology news', $filters['searchTerm']);
        $this->assertEquals('2024-01-01', $filters['from_date']);
        $this->assertEquals('2024-12-31', $filters['to_date']);
        $this->assertEquals('title', $filters['sort']);
        $this->assertEquals('asc', $filters['order']);
        $this->assertEquals(50, $filters['per_page']);
        $this->assertEquals(3, $filters['page']);
    }



    /**
     * Test building filters with null request
     */
    public function test_build_with_null_request(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $preference = $this->createMockPreference([
            'default_sort' => 'published_at',
            'default_order' => 'desc',
            'articles_per_page' => 15,
            'preferred_authors' => [],
        ]);

        $sources = $this->createMockSources(['guardian']);
        $categories = collect([]);

        $preference->shouldReceive('getAttribute')->with('preferredSources')->andReturn($sources);
        $preference->shouldReceive('getAttribute')->with('preferredCategories')->andReturn($categories);
        $preference->shouldReceive('load')->with(['preferredSources', 'preferredCategories'])->andReturnSelf();

        $user->shouldReceive('getOrCreatePreference')->once()->andReturn($preference);

        // Act
        $filters = $this->builder->build($user, null);

        // Assert
        $this->assertEquals(['guardian'], $filters['source']);
        $this->assertArrayNotHasKey('searchTerm', $filters);
        $this->assertArrayNotHasKey('page', $filters);
    }

    // ==================== Helper Methods ====================

    /**
     * Create a mock User
     */
    protected function createMockUser(): User|Mockery\MockInterface
    {
        return Mockery::mock(User::class);
    }

    /**
     * Create a mock UserPreference with given attributes
     */
    protected function createMockPreference(array $attributes): UserPreference|Mockery\MockInterface
    {
        $preference = Mockery::mock(UserPreference::class);
        $preference->shouldIgnoreMissing();
        
        foreach ($attributes as $key => $value) {
            $preference->shouldReceive('getAttribute')->with($key)->andReturn($value);
            $preference->shouldReceive('__get')->with($key)->andReturn($value);
        }

        return $preference;
    }

    /**
     * Create a mock collection of sources
     */
    protected function createMockSources(array $slugs): Collection
    {
        $sources = collect();
        
        foreach ($slugs as $slug) {
            $source = new \stdClass();
            $source->slug = $slug;
            $sources->push($source);
        }

        return $sources;
    }

    /**
     * Create a mock collection of categories
     */
    protected function createMockCategories(array $slugs): Collection
    {
        $categories = collect();
        
        foreach ($slugs as $slug) {
            $category = new \stdClass();
            $category->slug = $slug;
            $categories->push($category);
        }

        return $categories;
    }
}

