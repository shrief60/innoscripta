<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed sources first (required for user preferences)
        $this->call([
            SourcesSeeder::class,
        ]);

        // Note: UserPreferencesSeeder should be run AFTER fetching articles
        // so that categories exist. Run it manually:
        // php artisan db:seed --class=UserPreferencesSeeder
        //
        // Or uncomment below if categories already exist:
        // $this->call([
        //     UserPreferencesSeeder::class,
        // ]);
    }
}
