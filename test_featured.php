<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;

$featured = Article::with(['images', 'user', 'category'])
    ->where('is_published', 1)
    ->orderBy('is_boosted', 'desc')
    ->orderBy('created_at', 'desc')
    ->take(12)
    ->get();

echo "Featured count: " . $featured->count() . "\n";
foreach ($featured as $article) {
    echo " - " . $article->titre . " (id: " . $article->id . ", published: " . $article->is_published . ")\n";
}
?>
