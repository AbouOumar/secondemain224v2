<?php

namespace App\Models;

use App\Services\Article\ImageCompressionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class ArticleImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'url',
        'thumb_path',
        'ordre',
    ];

    protected static function booted(): void
    {
        // Vignette créée automatiquement, quel que soit l'endroit où l'image est ajoutée.
        static::created(function (ArticleImage $image) {
            $image->generateThumbnail();
        });

        static::deleted(function (ArticleImage $image) {
            // thumb_path peut pointer vers l'originale quand elle est déjà légère.
            if ($image->thumb_path && $image->thumb_path !== ($image->getAttributes()['url'] ?? null)) {
                Storage::disk('public')->delete($image->thumb_path);
            }
        });
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Retourne le chemin retenu pour les listes (vignette ou originale), ou null.
     */
    public function generateThumbnail(): ?string
    {
        // Chemin brut (sans l'URL ajoutée par l'accesseur), disponible dès l'événement « created ».
        $path = $this->getAttributes()['url'] ?? null;
        $thumb = $path ? app(ImageCompressionService::class)->makeThumbnail($path) : null;

        if ($thumb) {
            $this->thumb_path = $thumb;
            $this->saveQuietly();
        }

        return $thumb;
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => asset('storage/' . $value),
        );
    }

    /**
     * Version légère pour les listes ; image d'origine si la vignette n'existe pas.
     */
    protected function thumbUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumb_path ? asset('storage/' . $this->thumb_path) : $this->url,
        );
    }
}
