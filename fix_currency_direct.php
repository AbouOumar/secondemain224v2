<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Direct database update to avoid enum validation
$updated = DB::table('articles')
    ->where('currency', 'gnf')
    ->update(['currency' => 'GNF']);

echo "Updated {$updated} article(s) from 'gnf' to 'GNF'\n";

$updated = DB::table('articles')
    ->where('currency', 'fcfa')
    ->update(['currency' => 'FCFA']);

echo "Updated {$updated} article(s) from 'fcfa' to 'FCFA'\n";

$updated = DB::table('articles')
    ->where('currency', 'usd')
    ->update(['currency' => 'USD']);

echo "Updated {$updated} article(s) from 'usd' to 'USD'\n";

$updated = DB::table('articles')
    ->where('currency', 'eur')
    ->update(['currency' => 'EUR']);

echo "Updated {$updated} article(s) from 'eur' to 'EUR'\n";

// Verify the fix
$articles = DB::table('articles')->get(['id', 'currency']);
echo "\nCurrent currency values:\n";
foreach($articles as $article) {
    echo "ID: {$article->id}, Currency: [{$article->currency}]\n";
}
?>