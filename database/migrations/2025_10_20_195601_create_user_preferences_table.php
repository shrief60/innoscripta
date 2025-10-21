<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Main user preferences (settings)
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('default_sort')->default('published_at'); // published_at, created_at, title
            $table->string('default_order')->default('desc'); // asc, desc
            $table->integer('articles_per_page')->default(20);
            $table->timestamps();
            
            $table->unique('user_id');
        });

        // User's preferred sources (many-to-many)
        Schema::create('user_preferred_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['user_id', 'source_id']);
        });

        // User's preferred categories (many-to-many)
        Schema::create('user_preferred_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['user_id', 'category_id']);
        });

        // User's preferred authors
        Schema::create('user_preferred_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            $table->timestamps();
            
            $table->unique(['user_id', 'author_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferred_authors');
        Schema::dropIfExists('user_preferred_categories');
        Schema::dropIfExists('user_preferred_sources');
        Schema::dropIfExists('user_preferences');
    }
};
