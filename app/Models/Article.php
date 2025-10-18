<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'content', 'source_id', 'author', 'category_id', 'url', 'thumbnail', 'published_at', 'fetched_at'];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
