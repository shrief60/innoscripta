<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class CategoryService
{
    /**
     * Cache to avoid repeated database queries within the same request
     */
    protected static array $categoryCache = [];

    /**
     * Get or create a category by name and return its ID
     * Thread-safe with exception handling for race conditions
     * 
     * @param string $categoryName
     * @return int|null
     */
    public function getOrCreateCategoryId(string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $slug = Str::slug($categoryName);

        //avoids repeated DB queries in same request
        if (isset(self::$categoryCache[$slug])) {
            return self::$categoryCache[$slug];
        }

        $category = Category::where('slug', $slug)->first();

        if ($category) {
            self::$categoryCache[$slug] = $category->id;
            return $category->id;
        }

        try {
            $category = Category::create([
                'slug' => $slug,
                'name' => $categoryName,
            ]);

            self::$categoryCache[$slug] = $category->id;
            return $category->id;

        } catch (QueryException $e) {
            // If duplicate key error
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $category = Category::where('slug', $slug)->first();
                
                if ($category) {
                    self::$categoryCache[$slug] = $category->id;
                    return $category->id;
                }
            }

            // If it's another type of error, log and return null
            Log::error('Category creation failed', [
                'slug' => $slug,
                'name' => $categoryName,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

}

