<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

interface PersonalizedFeedServiceInterface
{
    /**
     * Get personalized article feed for user
     * 
     * @param User $user
     * @param Request|null $request
     * @return array ['articles' => LengthAwarePaginator, 'meta' => array]
     */
    public function getPersonalizedFeed(User $user, ?Request $request = null): array;

}

