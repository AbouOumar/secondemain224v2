<?php

namespace App\Console\Commands;

use App\Models\ArticleImage;
use Illuminate\Console\Command;

class GenerateImageThumbnails extends Command
{
    protected $signature = 'images:thumbnails';

    protected $description = 'Crée les vignettes manquantes des photos d\'annonces (sans toucher aux originaux)';

    public function handle(): int
    {
        $created = 0;
        $failed = 0;

        ArticleImage::whereNull('thumb_path')->chunkById(100, function ($images) use (&$created, &$failed) {
            foreach ($images as $image) {
                $image->generateThumbnail() ? $created++ : $failed++;
            }
        });

        $this->info("{$created} vignette(s) créée(s)" . ($failed ? ", {$failed} image(s) introuvable(s) ou illisible(s)." : '.'));

        return self::SUCCESS;
    }
}
