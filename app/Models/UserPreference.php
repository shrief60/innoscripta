<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'default_sort',
        'default_order',
        'articles_per_page',
    ];

    protected $casts = [
        'articles_per_page' => 'integer',
    ];

    /**
     * Get the user that owns the preferences
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user's preferred sources
     */
    public function preferredSources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class, 'user_preferred_sources', 'user_id', 'source_id')
            ->withTimestamps();
    }

    /**
     * Get the user's preferred categories
     */
    public function preferredCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_preferred_categories', 'user_id', 'category_id')
            ->withTimestamps();
    }

    /**
     * Sync preferred authors
     * 
     * @param array $authors
     * @return void
     */
    public function syncPreferredAuthors(array $authors): void
    {
        // Remove existing
        DB::table('user_preferred_authors')
            ->where('user_id', $this->user_id)
            ->delete();

        // Add new ones
        if (!empty($authors)) {
            $data = collect($authors)->map(fn($author) => [
                'user_id' => $this->user_id,
                'author_name' => $author,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            DB::table('user_preferred_authors')->insert($data);
        }
    }
}
