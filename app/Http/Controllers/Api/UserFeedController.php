<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PersonalizedFeedServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class UserFeedController extends Controller
{
    public function __construct(
        protected PersonalizedFeedServiceInterface $feedService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/feed",
     *     tags={"User Feed"},
     *     summary="Get personalized article feed",
     *     description="Retrieve articles based on user preferences (sources, categories, authors). Query parameters can override or extend preferences.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         description="Search term (extends user preferences)",
     *         required=false,
     *         @OA\Schema(type="string", example="technology")
     *     ),
     *     @OA\Parameter(
     *         name="source[]",
     *         in="query",
     *         description="Additional sources to include (merges with preferred sources)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string", example="bbc"))
     *     ),
     *     @OA\Parameter(
     *         name="category[]",
     *         in="query",
     *         description="Additional categories to include (merges with preferred categories)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string", example="science"))
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
     *         description="Override user's default sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"published_at", "created_at", "title"}, example="published_at")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Override user's default sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Override user's default items per page (1-100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=10)
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
     *         description="Personalized feed retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Latest Technology News"),
     *                     @OA\Property(property="source", type="object"),
     *                     @OA\Property(property="category", type="object")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="personalization",
     *                 type="object",
     *                 @OA\Property(property="personalized", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="filters_applied",
     *                     type="object",
     *                     @OA\Property(property="sources", type="array", @OA\Items(type="string", example="guardian")),
     *                     @OA\Property(property="categories", type="array", @OA\Items(type="string", example="technology")),
     *                     @OA\Property(property="has_author_filter", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(
     *                     property="user_preferences",
     *                     type="object",
     *                     @OA\Property(property="articles_per_page", type="integer", example=20),
     *                     @OA\Property(property="default_sort", type="string", example="published_at"),
     *                     @OA\Property(property="default_order", type="string", example="desc")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(Request $request) : AnonymousResourceCollection
    {
        $user = Auth::user();
        
        // Get personalized feed from service
        $result = $this->feedService->getPersonalizedFeed($user, $request);

        return ArticleResource::collection($result['articles'])
            ->additional(['personalization' => $result['meta']]);
    }
}
