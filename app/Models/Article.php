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
        'colors',
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
        'rejection_raison',
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

    /**
     * Filtre une requête selon un jeu de critères de recherche communs —
     * utilisé à la fois par la recherche du site (HomeController::search)
     * et par la vérification des alertes de recherche (SearchAlertService),
     * pour ne définir "qu'est-ce qu'une annonce qui correspond" qu'à un
     * seul endroit.
     *
     * Clés reconnues : search, category (ou category_id), min_price,
     * max_price, etat, localisation.
     */
    public function scopeMatchingCriteria($query, array $filters)
    {
        $category = $filters['category'] ?? $filters['category_id'] ?? null;

        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(fn ($q2) => $q2->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($category, fn ($q, $value) => $q->where('category_id', $value))
            ->when($filters['min_price'] ?? null, fn ($q, $value) => $q->where('prix', '>=', $value))
            ->when($filters['max_price'] ?? null, fn ($q, $value) => $q->where('prix', '<=', $value))
            ->when($filters['etat'] ?? null, fn ($q, $value) => $q->where('etat', $value))
            ->when($filters['localisation'] ?? null, fn ($q, $value) => $q->where('localisation', 'like', "%{$value}%"));
    }

    protected function casts(): array
    {
        return [
            'currency' => ArticleCurrency::class,
            'etat' => ArticleEtat::class,
            'colors' => 'array',
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

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}
