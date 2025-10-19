<?php

namespace App\Services\Adapters;

use App\Contracts\NewsSourceInterface;
use App\Models\Source;
use App\Models\Category;
use App\Services\HttpClientService;
use Illuminate\Support\Str;
use App\DTO\ArticleDTO;
use App\Utils\DateTimeHelper;

class NewsApiAdapter implements NewsSourceInterface
{
    public function __construct(
        protected HttpClientService $httpClient
    ) {}

    public function fetch(): array
    {
        $source = Source::where('slug', 'newsapi')->first();
        
        $url = config('news.newsapi.base_url') . config('news.newsapi.headlines_endpoint');
        
        $response = $this->httpClient->get($url, [
            'apiKey' => config('news.newsapi.key'),
            'sources' => 'techcrunch,the-verge,the-wall-street-journal',
        ]);

        if (!$response) {
            return [];
        }

        return collect($response->json('articles', []))
            ->filter(fn($a) => !empty($a['title']) && !empty($a['url'])) // Filter out invalid articles
            ->map(fn($a) => new ArticleDTO(
                merchant_id: md5($a['url']), // NewsAPI doesn't provide ID, use URL hash
                title: $a['title'],
                slug: Str::slug($a['title']),
                description: $a['description'] ?? null,
                content: $a['content'] ?? null,
                source_id: $source->id,
                author: $a['author'] ?? null,
                category_id: null, // NewsAPI doesn't provide categories
                url: $a['url'],
                thumbnail: $a['urlToImage'] ?? null,
                publishedAt: DateTimeHelper::parseDateTime($a['publishedAt'] ?? null),
            ))
            ->toArray();
    }
}