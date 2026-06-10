<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;

// Fix articles with lowercase currency values
$articles = Article::all();
$fixed = 0;

foreach ($articles as $article) {
    $original = $article->currency;
    
    // Convert lowercase to uppercase if needed
    if ($original === 'gnf') {
        $article->currency = 'GNF';
        $article->save();
        echo "Fixed article ID {$article->id}: changed currency from '{$original}' to 'GNF'\n";
        $fixed++;
    } elseif ($original === 'fcfa') {
        $article->currency = 'FCFA';
        $article->save();
        echo "Fixed article ID {$article->id}: changed currency from '{$original}' to 'FCFA'\n";
        $fixed++;
    } elseif ($original === 'usd') {
        $article->currency = 'USD';
        $article->save();
        echo "Fixed article ID {$article->id}: changed currency from '{$original}' to 'USD'\n";
        $fixed++;
    } elseif ($original === 'eur') {
        $article->currency = 'EUR';
        $article->save();
        echo "Fixed article ID {$article->id}: changed currency from '{$original}' to 'EUR'\n";
        $fixed++;
    }
}

if ($fixed === 0) {
    echo "No articles needed fixing - all currency values are already correct.\n";
} else {
    echo "Fixed {$fixed} article(s) with incorrect currency values.\n";
}

// Show final state
echo "\nCurrent currency values in articles table:\n";
$articles = Article::all();
foreach($articles as $article) {
    echo 'ID: ' . $article->id . ', Currency: [' . $article->currency . ']' . PHP_EOL;
}
?>