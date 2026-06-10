<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ArticleImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'url',
        'ordre',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => asset('storage/' . $value),
        );
    }
}
