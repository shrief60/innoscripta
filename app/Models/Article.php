<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_id' ,'title', 'slug', 'description', 'content', 'source_id', 'author', 'category_id', 'url', 'thumbnail', 'published_at', 'fetched_at'];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope: Filter by source slug or ID
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|string $sources
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, $sources)
    {
        $sources = is_array($sources) ? $sources : [$sources];
        
        return $query->whereHas('source', function($q) use ($sources) {
            $q->whereIn('slug', $sources)->orWhereIn('id', $sources);
        });
    }

    /**
     * Scope: Filter by category slug or ID
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|string $categories
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, $categories)
    {
        $categories = is_array($categories) ? $categories : [$categories];
        
        return $query->whereHas('category', function($q) use ($categories) {
            $q->whereIn('slug', $categories)->orWhereIn('id', $categories);
        });
    }

    /**
     * Scope: Filter by author (partial match)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $author
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAuthor($query, $author)
    {
        return $query->where('author', 'like', '%' . $author . '%');
    }

    /**
     * Scope: Filter by date range
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, $fromDate = null, $toDate = null)
    {
        if ($fromDate) {
            $query->where('published_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('published_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Scope: Sort articles by allowed field
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortBy
     * @param string $order
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortBy($query, $sortBy = 'published_at', $order = 'desc')
    {
        $allowedSorts = ['published_at', 'created_at', 'title'];
        
        if (in_array($sortBy, $allowedSorts)) {
            return $query->orderBy($sortBy, $order);
        }
        
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope: Full-text search in title, description, and content
     * Uses MySQL FULLTEXT index for efficient searching
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        // Use MySQL FULLTEXT search if available
        if (config('database.default') === 'mysql') {
            return $query->whereRaw(
                "MATCH(title, description, content) AGAINST(? IN BOOLEAN MODE)",
                [$searchTerm]
            );
        }

        // Fallback to LIKE search for other databases (slower)
        return $query->where(function($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhere('content', 'like', "%{$searchTerm}%");
        });
    }
}
