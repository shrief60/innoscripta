<?php

namespace App\Contracts;

interface ArticleRepositoryInterface
{
    /**
     * Search articles with filters
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $filters = []);

    /**
     * Store multiple articles
     * 
     * @param array $articles
     * @return array
     */
    public function storeMany(array $articles): array;
}

