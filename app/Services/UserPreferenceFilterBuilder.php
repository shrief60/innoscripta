<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;


class UserPreferenceFilterBuilder
{
    /**
     * Build complete filters from user preferences and optional request parameters
     * 
     * @param User $user
     * @param Request|null $request
     * @return array
     */
    public function build(User $user, ?Request $request = null): array
    {
        $preference = $user->getOrCreatePreference();
        $preference->load(['preferredSources', 'preferredCategories']);

        $filters = $this->buildFromPreferences($preference);

        if ($request) {
            $filters = $this->mergeRequestParameters($request, $filters);
        }

        return $filters;
    }

    /**
     * Build filters array from user preferences
     * 
     * @param UserPreference $preference
     * @return array
     */
    protected function buildFromPreferences(UserPreference $preference): array
    {
        $filters = [];

        if ($preference->preferredSources->isNotEmpty()) {
            $filters['source'] = $preference->preferredSources->pluck('slug')->toArray();
        }

        if ($preference->preferredCategories->isNotEmpty()) {
            $filters['category'] = $preference->preferredCategories->pluck('slug')->toArray();
        }

        $preferredAuthors = $preference->preferred_authors;
        if (!empty($preferredAuthors)) {
            $filters['preferred_authors'] = $preferredAuthors;
        }

        $filters['sort'] = $preference->default_sort;
        $filters['order'] = $preference->default_order;
        $filters['per_page'] = $preference->articles_per_page;

        return $filters;
    }

    /**
     * Merge request parameters with preference-based filters
     * Request parameters can override or expand preferences
     * 
     * @param Request $request
     * @param array $filters
     * @return array
     */
    protected function mergeRequestParameters(Request $request, array $filters): array
    {
        $this->applyOverridableParameters($request, $filters);
        $this->expandArrayFilters($request, $filters);

        return $filters;
    }

    /**
     * Apply overridable single-value parameters
     * 
     * @param Request $request
     * @param array $filters
     * @return void
     */
    protected function applyOverridableParameters(Request $request, array &$filters): void
    {
        $overridableParams = ['sort', 'order', 'per_page', 'page', 'searchTerm', 'from_date', 'to_date'];
        
        foreach ($overridableParams as $param) {
            if ($request->has($param)) {
                $filters[$param] = $request->input($param);
            }
        }
    }

    /**
     * Expand array filters (sources, categories) by merging with request
     * 
     * @param Request $request
     * @param array $filters
     * @return void
     */
    protected function expandArrayFilters(Request $request, array &$filters): void
    {
        if ($request->has('source')) {
            $requestSources = $this->normalizeArrayInput($request->input('source'));
            $filters['source'] = array_unique(array_merge(
                $filters['source'] ?? [],
                $requestSources
            ));
        }

        if ($request->has('category')) {
            $requestCategories = $this->normalizeArrayInput($request->input('category'));
            $filters['category'] = array_unique(array_merge(
                $filters['category'] ?? [],
                $requestCategories
            ));
        }
    }

    /**
     * Normalize input to array format
     * 
     * @param mixed $input
     * @return array
     */
    protected function normalizeArrayInput(mixed $input): array
    {
        return is_array($input) ? $input : [$input];
    }
}

