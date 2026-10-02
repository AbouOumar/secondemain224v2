<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Déplace les pièces d'identité et selfies du disque public (accessibles
 * par URL) vers le disque privé. Les chemins relatifs restent identiques,
 * donc la base de données n'a pas à changer.
 */
return new class extends Migration
{
    private const DIRECTORIES = ['verification_documents', 'verification_selfies'];

    public function up(): void
    {
        $this->moveAll(Storage::disk('public'), Storage::disk('local'));
    }

    public function down(): void
    {
        $this->moveAll(Storage::disk('local'), Storage::disk('public'));
    }

    private function moveAll($from, $to): void
    {
        foreach (self::DIRECTORIES as $directory) {
            foreach ($from->allFiles($directory) as $path) {
                if (! $to->exists($path)) {
                    $stream = $from->readStream($path);
                    $to->writeStream($path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if ($to->exists($path) && $to->size($path) === $from->size($path)) {
                    $from->delete($path);
                } else {
                    Log::warning("Document d'identité non déplacé : {$path}");
                }
            }

            // Ne supprimer le dossier que si tout a été déplacé.
            if ($from->allFiles($directory) === []) {
                $from->deleteDirectory($directory);
            }
        }
    }
};
