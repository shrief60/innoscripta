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
     * @OA\Get(
     *     path="/api/v1/articles",
     *     tags={"Articles"},
     *     summary="Get articles with filtering",
     *     description="Retrieve articles with optional filters (sources, categories, authors, date range, search)",
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         description="Search in title, description, and content",
     *         required=false,
     *         @OA\Schema(type="string", example="technology")
     *     ),
     *     @OA\Parameter(
     *         name="source[]",
     *         in="query",
     *         description="Filter by source slug (can be multiple)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string", example="guardian"))
     *     ),
     *     @OA\Parameter(
     *         name="category[]",
     *         in="query",
     *         description="Filter by category slug (can be multiple)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string", example="technology"))
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author name",
     *         required=false,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter articles from this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-10-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter articles until this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-10-21")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"published_at", "created_at", "title"}, example="published_at")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (1-100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Articles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Latest Technology News"),
     *                     @OA\Property(property="description", type="string", example="Description of the article"),
     *                     @OA\Property(property="url", type="string", example="https://example.com/article"),
     *                     @OA\Property(property="published_at", type="string", format="date-time", example="2025-10-21T10:00:00Z"),
     *                     @OA\Property(
     *                         property="source",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="The Guardian"),
     *                         @OA\Property(property="slug", type="string", example="guardian")
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Technology"),
     *                         @OA\Property(property="slug", type="string", example="technology")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function index(ArticleIndexRequest $request) : AnonymousResourceCollection
    {
        $validated = $request->validated();

        $articles = $this->repository->search($validated);

        return ArticleResource::collection($articles);
    }
}


