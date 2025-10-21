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
     * Get personalized article feed based on user preferences
     * 
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
