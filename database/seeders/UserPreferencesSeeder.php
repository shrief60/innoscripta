<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPreference;
use App\Models\Source;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserPreferencesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create sources
        $sources = Source::all();
        if ($sources->isEmpty()) {
            $this->command->warn('No sources found. Please run SourcesSeeder first.');
            return;
        }

        // Get or create categories
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->command->info('No categories found yet. They will be created when articles are fetched.');
        }

        $this->command->info('Seeding user preferences...');

        // Create test users with different preference profiles
        $this->seedTechEnthusiast($sources, $categories);
        $this->seedPoliticsFollower($sources, $categories);
        $this->seedBusinessReader($sources, $categories);
        $this->seedGeneralReader($sources, $categories);

        $this->command->info('User preferences seeded successfully!');
    }

    /**
     * Tech Enthusiast User
     * Preferences: Technology, Guardian + NYT, follows tech journalists
     */
    protected function seedTechEnthusiast($sources, $categories): void
    {
        $user = User::updateOrCreate(
            ['email' => 'tech.enthusiast@example.com'],
            [
                'name' => 'Tech Enthusiast',
                'password' => Hash::make('password'),
            ]
        );

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'default_sort' => 'published_at',
                'default_order' => 'desc',
                'articles_per_page' => 50,
            ]
        );

        // Preferred sources: Guardian and NYT
        $guardianAndNyt = $sources->whereIn('slug', ['guardian', 'nyt'])->pluck('id')->toArray();
        if (!empty($guardianAndNyt)) {
            $preference->preferredSources()->sync($guardianAndNyt);
        }

        // Preferred categories: Technology
        if ($categories->isNotEmpty()) {
            $techCategory = $categories->firstWhere('slug', 'movies');
            if ($techCategory) {
                $preference->preferredCategories()->sync([$techCategory->id]);
            }
        }

        // Preferred authors
        $preference->syncPreferredAuthors([
            'Kara Swisher',
            'Walt Mossberg',
            'David Pierce',
        ]);

        $this->command->info("✓ Created Tech Enthusiast ({$user->email})");
    }

    /**
     * Politics Follower User
     * Preferences: Politics, all sources, follows political journalists
     */
    protected function seedPoliticsFollower($sources, $categories): void
    {
        $user = User::updateOrCreate(
            ['email' => 'politics.follower@example.com'],
            [
                'name' => 'Politics Follower',
                'password' => Hash::make('password'),
            ]
        );

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'default_sort' => 'published_at',
                'default_order' => 'desc',
                'articles_per_page' => 20,
            ]
        );

        // Preferred sources: All sources (no filter)
        // Don't sync any sources to show all

        // Preferred categories: Politics, World
        if ($categories->isNotEmpty()) {
            $politicsCategories = $categories->whereIn('slug', ['politics', 'world', 'us-news'])->pluck('id')->toArray();
            if (!empty($politicsCategories)) {
                $preference->preferredCategories()->sync($politicsCategories);
            }
        }

        // Preferred authors
        $preference->syncPreferredAuthors([
            'Maggie Haberman',
            'Peter Baker',
            'Jonathan Martin',
        ]);

        $this->command->info("✓ Created Politics Follower ({$user->email})");
    }

    /**
     * Business Reader User
     * Preferences: Business, NYT only, different display settings
     */
    protected function seedBusinessReader($sources, $categories): void
    {
        $user = User::updateOrCreate(
            ['email' => 'business.reader@example.com'],
            [
                'name' => 'Business Reader',
                'password' => Hash::make('password'),
            ]
        );

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'default_sort' => 'published_at',
                'default_order' => 'desc',
                'articles_per_page' => 30,
            ]
        );

        // Preferred sources: NYT only
        $nyt = $sources->firstWhere('slug', 'nyt');
        if ($nyt) {
            $preference->preferredSources()->sync([$nyt->id]);
        }

        // Preferred categories: Business, Money
        if ($categories->isNotEmpty()) {
            $businessCategories = $categories->whereIn('slug', ['business', 'money', 'economy'])->pluck('id')->toArray();
            if (!empty($businessCategories)) {
                $preference->preferredCategories()->sync($businessCategories);
            }
        }

        // Preferred authors
        $preference->syncPreferredAuthors([
            'Andrew Ross Sorkin',
            'David Gelles',
        ]);

        $this->command->info("✓ Created Business Reader ({$user->email})");
    }

    /**
     * General Reader User
     * Preferences: Multiple categories, Guardian only, casual settings
     */
    protected function seedGeneralReader($sources, $categories): void
    {
        $user = User::updateOrCreate(
            ['email' => 'general.reader@example.com'],
            [
                'name' => 'General Reader',
                'password' => Hash::make('password'),
            ]
        );

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'default_sort' => 'published_at',
                'default_order' => 'desc',
                'articles_per_page' => 20,
            ]
        );

        // Preferred sources: Guardian
        $guardian = $sources->firstWhere('slug', 'guardian');
        if ($guardian) {
            $preference->preferredSources()->sync([$guardian->id]);
        }

        // Preferred categories: Multiple general interest categories
        if ($categories->isNotEmpty()) {
            $generalCategories = $categories->whereIn('slug', ['technology', 'sport', 'culture', 'world'])->pluck('id')->toArray();
            if (!empty($generalCategories)) {
                $preference->preferredCategories()->sync($generalCategories);
            }
        }

        // Preferred authors: None (follows topics, not authors)
        $preference->syncPreferredAuthors([]);

        $this->command->info("✓ Created General Reader ({$user->email})");
    }
}
