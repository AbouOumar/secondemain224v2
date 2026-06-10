<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$articles = \App\Models\Article::all();
echo "Total d'articles: " . $articles->count() . "\n";

if ($articles->count() > 0) {
  $published = \App\Models\Article::where('is_published', true)->count();
  $unpublished = \App\Models\Article::where('is_published', false)->count();
  echo "Articles publiés: $published\n";
  echo "Articles non publiés: $unpublished\n";
  echo "\nPremiers 5 articles:\n";
  \App\Models\Article::limit(5)->get()->each(function($a) {
    echo "  ID: {$a->id}, Titre: {$a->titre}, Published: " . ($a->is_published ? 'OUI' : 'NON') . "\n";
  });
} else {
  echo "Aucun article trouvé!\n";
}
