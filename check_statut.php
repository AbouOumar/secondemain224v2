<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if statut column exists by trying to select it
try {
    $articles = DB::table('articles')->select('id', 'titre', 'statut')->take(5)->get();
    echo "Statut column exists!\n";
    foreach ($articles as $article) {
        echo "ID: {$article->id}, Titre: {$article->titre}, Statut: {$article->statut}\n";
    }
} catch (\Exception $e) {
    echo "Statut column does not exist: " . $e->getMessage() . "\n";
}

// Check all columns again to be sure
$columns = DB::select("PRAGMA table_info(articles)");
$columnNames = array_column($columns, 'name');
echo "\nAll columns: " . implode(', ', $columnNames) . "\n";

// Check if there's any reference to statut in the codebase
echo "\nSearching for statut references in codebase...\n";
$output = shell_exec('find . -name "*.php" -exec grep -l "statut" {} \; 2>/dev/null');
echo "Files containing 'statut':\n";
echo $output;
?>