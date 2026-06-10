<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\Web\HomeController();
try {
    $response = $controller->index();
    echo "HomeController::index() returned successfully\n";
    echo "Response type: " . get_class($response) . "\n";
    
    // Try to get the articles from the database directly
    $articles = \App\Models\Article::where('is_published', 1)
        ->orderBy('is_boosted', 'desc')
        ->orderBy('created_at', 'desc')
        ->take(12)
        ->get();
    echo "Articles from DB: " . $articles->count() . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\nFull trace:\n";
    echo $e->getTraceAsString() . "\n";
}
