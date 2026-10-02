<?php
namespace App\Services\Article;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Photos d'annonces : ré-encodage en WebP (taille max 1600 px) et vignette
 * de 480 px pour les listes. Le ré-encodage garantit aussi que le fichier
 * stocké est une vraie image, avec une extension choisie par le serveur.
 */
class ImageCompressionService {
    public const MAX_SIZE = 1600;
    public const THUMB_SIZE = 480;
    private const QUALITY = 80;

    public function compressAndStore(UploadedFile $file, string $path = 'articles'): string {
        try {
            $image = $this->load($file->getRealPath());
            if ($image) {
                $filepath = $path . '/' . Str::random(40) . '.webp';
                Storage::disk('public')->put($filepath, $this->encode($this->resize($image, self::MAX_SIZE)));
                return $filepath;
            }
        } catch (Throwable $e) {
            Log::warning('Compression image impossible, original conservé', ['error' => $e->getMessage()]);
        }

        // Repli (GD indisponible) : nom aléatoire et extension déduite du contenu, jamais celle du client.
        return $file->store($path, 'public');
    }

    /**
     * Crée la vignette d'une image déjà stockée et retourne son chemin.
     * Si elle ne serait pas plus légère que l'originale (photo déjà petite),
     * retourne le chemin de l'originale. Null si l'image est introuvable ou illisible.
     */
    public function makeThumbnail(string $path): ?string {
        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return null;
        }

        try {
            $image = $this->load($disk->path($path));
            if (!$image) {
                return null;
            }
            $thumbPath = dirname($path) . '/thumbs/' . pathinfo($path, PATHINFO_FILENAME) . '.webp';
            $bytes = $this->encode($this->resize($image, self::THUMB_SIZE));

            if (strlen($bytes) >= $disk->size($path)) {
                $disk->delete($thumbPath); // ancienne vignette plus lourde, le cas échéant
                return $path;
            }

            $disk->put($thumbPath, $bytes);
            return $thumbPath;
        } catch (Throwable $e) {
            Log::warning('Vignette impossible', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function delete(string $url): void {
        Storage::disk('public')->delete($url);
    }

    private function load(string $file): ?GdImage {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $image = match (@getimagesize($file)[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
            default => false,
        };

        if (!$image) {
            return null;
        }

        // Photos de téléphone : appliquer l'orientation EXIF pour ne pas les afficher couchées.
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($file)['Orientation'] ?? 1) : 1;
        $rotated = match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }

    private function resize(GdImage $image, int $max): GdImage {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, $max / max($width, $height));

        if ($ratio >= 1) {
            return $image;
        }

        $resized = imagecreatetruecolor((int) round($width * $ratio), (int) round($height * $ratio));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);

        return $resized;
    }

    private function encode(GdImage $image): string {
        imagesavealpha($image, true);
        ob_start();
        imagewebp($image, null, self::QUALITY);
        return (string) ob_get_clean();
    }
}
