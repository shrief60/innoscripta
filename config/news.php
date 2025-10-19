<?php

return [


    'sources' => [
        'guardian' => \App\Services\Adapters\GuardianAdapter::class,
        'newsapi' => \App\Services\Adapters\NewsApiAdapter::class,
        'nyt' => \App\Services\Adapters\NytAdapter::class,
    ],


    'guardian' => [
        'key' => env('GUARDIAN_API_KEY'),
        'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
        'search_endpoint' => '/search',
    ],


    'newsapi' => [
        'key' => env('NEWSAPI_KEY'),
        'base_url' => env('NEWSAPI_BASE_URL', 'https://newsapi.org/v2'),
        'headlines_endpoint' => '/top-headlines',
    ],


    'nytimes' => [
        'key' => env('NYTIMES_API_KEY'),
        'base_url' => env('NYTIMES_BASE_URL', 'https://api.nytimes.com/svc'),
        'topstories_endpoint' => '/topstories/v2/arts.json',
    ],

];

