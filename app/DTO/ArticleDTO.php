<?php

namespace App\DTO;


class ArticleDTO
{
    public function __construct(
        public string $merchant_id,
        public string $title,
        public string $slug,
        public ?string $description,
        public ?string $content,
        public int $source_id,
        public ?string $author,
        public ?int $category_id,
        public string $url,
        public ?string $thumbnail,
        public ?string $publishedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'merchant_id' => $this->merchant_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'source_id' => $this->source_id,
            'author' => $this->author,
            'category_id' => $this->category_id,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail,
            'published_at' => $this->publishedAt,
            'fetched_at' => now()->toDateTimeString(),
        ];
    }
}
