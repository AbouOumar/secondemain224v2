<?php

namespace App\Models;

use App\Enums\ArticleCurrency;
use App\Enums\ArticleEtat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'titre',
        'slug',
        'description',
        'prix',
        'currency',
        'stock',
        'etat',
        'annee',
        'localisation',
        'latitude',
        'longitude',
        'with_delivery',
        'delivery_prix',
        'is_boosted',
        'boosted_until',
        'is_verified',
        'is_published',
        'statut',
        'date_fin',
        'vue_count',
        'view_count',
        'contact_count',
        'last_viewed_at',
    ];

    public function scopeDisponible($query)
    {
        return $query->where(fn($q) =>
            $q->where('statut', '!=', 'vendu')->orWhereNull('statut')
        );
    }

    protected function casts(): array
    {
        return [
            'currency' => ArticleCurrency::class,
            'etat' => ArticleEtat::class,
            'with_delivery' => 'boolean',
            'is_boosted' => 'boolean',
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
            'boosted_until' => 'datetime',
            'deleted_at' => 'datetime',
            'view_count' => 'integer',
            'contact_count' => 'integer',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ArticleImage::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function boosts()
    {
        return $this->hasMany(Boost::class);
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'article_user_favorites');
    }
}
