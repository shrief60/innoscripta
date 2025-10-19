<?php

namespace App\Services\Adapters;

use App\Contracts\NewsSourceInterface;
use App\Models\Source;
use App\Services\HttpClientService;
use App\Services\CategoryService;
use Illuminate\Support\Str;
use App\DTO\ArticleDTO;
use App\Utils\DateTimeHelper;

class NytAdapter implements NewsSourceInterface
{
    public function __construct(
        protected HttpClientService $httpClient,
        protected CategoryService $categoryService
    ) {}

    public function fetch(): array
    {
        $source = Source::where('slug', 'nyt')->first();
        
        $url = config('news.nytimes.base_url') . config('news.nytimes.topstories_endpoint');
        
        $response = $this->httpClient->get($url, [
            'api-key' => config('news.nytimes.key'),
        ]);
        
        if (!$response) {
            return [];
        }

        return collect($response->json('results', []))
            ->filter(fn($a) => !empty($a['title']) && !empty($a['url']))
            ->map(function($a) use ($source) {
                return new ArticleDTO(
                    merchant_id: $a['uri'] ?? md5($a['url']), // NYT provides uri
                    title: $a['title'],
                    slug: Str::slug($a['title']),
                    description: $a['abstract'] ?? null, // NYT uses 'abstract' not 'description'
                    content: $a['abstract'] ?? null, // NYT doesn't provide full content
                    source_id: $source->id,
                    author: $a['byline'] ?? null, // NYT uses 'byline'
                    category_id: isset($a['section']) ? $this->categoryService->getOrCreateCategoryId($a['section']) : null,
                    url: $a['url'],
                    thumbnail: $a['multimedia'][0]['url'] ?? null, // Get first image
                    publishedAt: $a['published_date'] ?? now()->toDateTimeString(),
                );
            })
            ->toArray();
    }
}
