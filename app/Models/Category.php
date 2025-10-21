<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function articles() : HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function userPreferences() : BelongsToMany
    {
        return $this->belongsToMany(UserPreference::class, 'user_preferred_categories', 'category_id', 'user_id');
    }
}
