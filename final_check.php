<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check articles and their status
$articles = DB::table('articles')
    ->select('id', 'titre', 'is_published', 'is_verified', 'etat', 'prix', 'currency')
    ->get();

echo "All articles in database:\n";
echo "ID | Titre | Is_Pub | Is_Verif | Etat | Prix Currency\n";
echo "---|-------|--------|----------|------|--------------\n";

foreach ($articles as $article) {
    $pub = $article->is_published ? 'YES' : 'NO ';
    $ver = $article->is_verified ? 'YES' : 'NO ';
    echo str_pad($article->id, 3) . " | " . 
         str_pad(substr($article->titre, 0, 20), 20) . " | " . 
         str_pad($pub, 6) . " | " . 
         str_pad($ver, 8) . " | " . 
         str_pad($article->etat, 6) . " | " . 
         $article->prix . " " . $article->currency . "\n";
}

echo "\n\nArticles that should appear on homepage (is_published = 1):\n";
$published = DB::table('articles')
    ->where('is_published', 1)
    ->get();

echo "Count: " . $published->count() . "\n";
foreach ($published as $article) {
    echo "- ID {$article->id}: {$article->titre} ({$article->prix} {$article->currency})\n";
}
?>