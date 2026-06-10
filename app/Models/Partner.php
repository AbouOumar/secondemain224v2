<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom_magasin',
        'slug',
        'adresse',
        'description',
        'logo',
        'couverture',
        'telephone',
        'horaire',
        'is_verified',
        'abonnement_fin',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'abonnement_fin' => 'datetime',
            'horaire' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articles()
    {
        return $this->hasManyThrough(Article::class, User::class, 'id', 'user_id', 'user_id', 'id')
            ->where('is_published', true);
    }

    public function getUrlAttribute(): string
    {
        return route('magasin.show', $this->slug);
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->couverture ? asset('storage/' . $this->couverture) : asset('assets/img/hero-bg.jpg');
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo ? asset('storage/' . $this->logo) : asset('assets/img/icon.png');
    }
}
