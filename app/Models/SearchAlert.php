<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'search',
        'category_id',
        'min_price',
        'max_price',
        'etat',
        'localisation',
        'is_active',
        'last_matched_article_id',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
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

    /**
     * Résumé lisible des critères de l'alerte, pour l'affichage.
     */
    public function label(): string
    {
        $parts = [];
        if ($this->search) {
            $parts[] = '« ' . $this->search . ' »';
        }
        if ($this->category) {
            $parts[] = $this->category->libelle;
        }
        if ($this->min_price || $this->max_price) {
            $parts[] = match (true) {
                $this->min_price && $this->max_price => number_format($this->min_price, 0, ',', ' ') . ' - ' . number_format($this->max_price, 0, ',', ' ') . ' GNF',
                (bool) $this->min_price => 'à partir de ' . number_format($this->min_price, 0, ',', ' ') . ' GNF',
                default => 'jusqu\'à ' . number_format($this->max_price, 0, ',', ' ') . ' GNF',
            };
        }
        if ($this->etat) {
            $parts[] = ucfirst(str_replace('_', ' ', $this->etat));
        }
        if ($this->localisation) {
            $parts[] = $this->localisation;
        }

        return $parts ? implode(' · ', $parts) : 'Toutes les annonces';
    }
}
