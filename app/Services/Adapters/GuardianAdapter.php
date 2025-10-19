<?php

namespace App\Services\Adapters;

use App\Contracts\NewsSourceInterface;
use App\Models\Source;
use App\Services\HttpClientService;
use App\Services\CategoryService;
use Illuminate\Support\Str;
use App\DTO\ArticleDTO;
use App\Utils\DateTimeHelper;

class GuardianAdapter implements NewsSourceInterface
{
    public function __construct(
        protected HttpClientService $httpClient,
        protected CategoryService $categoryService
    ) {}

    public function fetch(): array
    {
        $source = Source::where('slug', 'guardian')->first();
        
        $url = config('news.guardian.base_url') . config('news.guardian.search_endpoint');

        $response = $this->httpClient->get($url, [
            'api-key' => config('news.guardian.key'),
            'show-fields' => 'trailText,headline,thumbnail,byline,body&show-refinements=all',
            'page-size' => 10,
        ]);

        if (!$response) {
            return [];
        }

        $results = $response->json('response.results', []);
        
        $sourceId = $source->id;

        return collect($results)
            ->filter(fn($a) => !empty($a['webTitle']) && !empty($a['webUrl']))
            ->map(function($a) use ($sourceId) {
                return new ArticleDTO(
                    merchant_id: $a['id'],
                    title: $a['webTitle'],
                    slug: Str::slug($a['webTitle']),
                    description: $a['fields']['headline'] ?? $a['fields']['trailText'] ?? null,
                    content: $a['fields']['body'] ?? $a['fields']['trailText'] ?? null,
                    source_id: $sourceId,
                    author: $a['fields']['byline'] ?? null,
                    category_id: $this->categoryService->getOrCreateCategoryId($a['sectionName'] ?? 'Uncategorized'),
                    url: $a['webUrl'],
                    thumbnail: $a['fields']['thumbnail'] ?? null,
                    publishedAt: DateTimeHelper::parseDateTime($a['webPublicationDate']),
                );
            })
            ->toArray();
    }
}
