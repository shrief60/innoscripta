<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Source extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'api_identifier', 'base_url'];

    public function articles() : HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function userPreferences() : BelongsToMany
    {
        return $this->belongsToMany(UserPreference::class, 'user_preferred_sources', 'source_id', 'user_id');
    }
}
