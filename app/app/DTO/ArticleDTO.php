<?php

namespace App\app\DTO;


class ArticleDTO
{
    public function __construct(
        public string $title,
        public string $slug,
        public ?string $description,
        public ?string $content,
        public string $source,
        public ?string $author,
        public ?string $category,
        public string $url,
        public ?string $thumbnail,
        public ?string $publishedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'source' => $this->source,
            'author' => $this->author,
            'category' => $this->category,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail,
            'published_at' => $this->publishedAt,
            'fetched_at' => now(),
        ];
    }
}
