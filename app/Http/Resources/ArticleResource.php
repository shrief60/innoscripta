<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->when($request->include_content, $this->content),
            'source' => SourceResource::make($this->whenLoaded('source')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'author' => $this->author,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail,
            'published_at' => $this->published_at,
            'fetched_at' => $this->fetched_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

