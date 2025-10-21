<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\Api\ArticleIndexRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleRepositoryInterface $repository
    ) {}

    /**
     * Display a listing of articles with optional filtering
     * 
     * @param ArticleIndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(ArticleIndexRequest $request) : AnonymousResourceCollection
    {
        $validated = $request->validated();

        $articles = $this->repository->search($validated);

        return ArticleResource::collection($articles);
    }
}


