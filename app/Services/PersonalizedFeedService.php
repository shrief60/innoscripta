<?php

namespace App\Services;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\PersonalizedFeedServiceInterface;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;

class PersonalizedFeedService implements PersonalizedFeedServiceInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $repository,
        protected UserPreferenceFilterBuilder $filterBuilder
    ) {}

    /**
     * Get personalized article feed for user
     * 
     * @param User $user
     * @param Request|null $request
     * @return array ['articles' => LengthAwarePaginator, 'meta' => array]
     */
    public function getPersonalizedFeed(User $user, ?Request $request = null): array
    {
        $filters = $this->filterBuilder->build($user, $request);

        $articles = $this->repository->search($filters);

        $preference = $user->getOrCreatePreference();
        $meta = $this->buildPersonalizationMeta($filters, $preference);

        return [
            'articles' => $articles,
            'meta' => $meta
        ];
    }

    /**
     * Build personalization metadata for response
     * 
     * @param array $filters
     * @param UserPreference $preference
     * @return array
     */
    public function buildPersonalizationMeta(array $filters, UserPreference $preference): array
    {
        return [
            'personalized' => true,
            'filters_applied' => [
                'sources' => $filters['source'] ?? [],
                'categories' => $filters['category'] ?? [],
                'has_author_filter' => isset($filters['preferred_authors']),
            ],
            'user_preferences' => [
                'articles_per_page' => $preference->articles_per_page,
                'default_sort' => $preference->default_sort,
                'default_order' => $preference->default_order,
            ]
        ];
    }
}

