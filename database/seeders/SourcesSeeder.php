<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SourcesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'The Guardian',
                'slug' => 'guardian',
                'api_identifier' => 'the-guardian',
                'base_url' => 'https://content.guardianapis.com',
            ],
            [
                'name' => 'NewsAPI',
                'slug' => 'newsapi',
                'api_identifier' => 'newsapi',
                'base_url' => 'https://newsapi.org/v2',
            ],
            [
                'name' => 'New York Times',
                'slug' => 'nyt',
                'api_identifier' => 'nyt',
                'base_url' => 'https://api.nytimes.com/svc',
            ],
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(
                ['slug' => $source['slug']],
                $source
            );
        }
    }
}


