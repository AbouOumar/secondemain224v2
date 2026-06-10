<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check what columns exist in articles table
$columns = DB::select("PRAGMA table_info(articles)");
echo "Articles table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->name} ({$column->type})\n";
}
echo "\n";

// Check sample articles with all their fields
$articles = DB::table('articles')->take(5)->get();
foreach ($articles as $article) {
    echo "Article ID: {$article->id}\n";
    echo "  Titre: {$article->titre}\n";
    echo "  Statut: " . (isset($article->statut) ? $article->statut : 'NOT FOUND') . "\n";
    echo "  Is published: " . ($article->is_published ? 'yes' : 'no') . "\n";
    echo "  Is verified: " . ($article->is_verified ? 'yes' : 'no') . "\n";
    echo "  Prix: {$article->prix} {$article->currency}\n";
    echo "\n";
}
?>