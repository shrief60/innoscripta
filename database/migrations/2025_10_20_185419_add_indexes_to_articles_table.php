<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Critical indexes - Used in almost every query
            // published_at is used for sorting in 90%+ of queries
            $table->index('published_at', 'articles_published_at_index');
            
            // Composite indexes for common filter + sort patterns
            // These are VERY efficient because MySQL can use them for both filtering AND sorting
            
            // Filter by source + sort by date (most common user query)
            $table->index(['source_id', 'published_at'], 'articles_source_published_index');
            
            // Filter by category + sort by date (second most common)
            $table->index(['category_id', 'published_at'], 'articles_category_published_index');
            
        });
        
        // Full-text index for keyword search in article content
        // This enables fast search queries like "Find articles about 'climate change'"
        // Only works on MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles ADD FULLTEXT INDEX articles_fulltext_index (title, description, content)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('articles_published_at_index');
            $table->dropIndex('articles_source_published_index');
            $table->dropIndex('articles_category_published_index');
        });
        
        // Drop full-text index
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles DROP INDEX articles_fulltext_index');
        }
    }
};
