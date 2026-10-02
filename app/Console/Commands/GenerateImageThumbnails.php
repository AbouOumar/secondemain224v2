<?php

namespace App\Console\Commands;

use App\Models\ArticleImage;
use Illuminate\Console\Command;

class GenerateImageThumbnails extends Command
{
    protected $signature = 'images:thumbnails {--all : Réexamine aussi les images qui ont déjà une vignette}';

    protected $description = 'Crée les vignettes manquantes des photos d\'annonces (sans toucher aux originaux)';

    public function handle(): int
    {
        $created = 0;
        $kept = 0;
        $failed = 0;

        $query = $this->option('all') ? ArticleImage::query() : ArticleImage::whereNull('thumb_path');

        $query->chunkById(100, function ($images) use (&$created, &$kept, &$failed) {
            foreach ($images as $image) {
                $path = $image->generateThumbnail();
                match (true) {
                    $path === null => $failed++,
                    $path === $image->getRawOriginal('url') => $kept++,
                    default => $created++,
                };
            }
        });

        $this->info("{$created} vignette(s) créée(s), {$kept} photo(s) déjà légère(s) gardée(s) telle(s) quelle(s)"
            . ($failed ? ", {$failed} image(s) introuvable(s) ou illisible(s)." : '.'));

        return self::SUCCESS;
    }
}
