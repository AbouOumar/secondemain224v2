<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\Web\HomeController();
try {
    $response = $controller->index();
    $html = $response->render();
    
    // Check if $featuredArticles is in the HTML
    if (strpos($html, 'article-item') !== false) {
        echo "✓ Articles are rendered in the HTML\n";
        // Count how many article items are in the HTML
        $count = substr_count($html, 'article-item');
        echo "  Found $count article items\n";
    } else {
        echo "✗ No articles found in HTML\n";
    }
    
    // Check for the spinner
    if (strpos($html, 'spinner-border') !== false) {
        echo "✗ Spinner found in HTML (articles not loaded)\n";
    } else {
        echo "✓ No spinner found\n";
    }
    
    echo "\n✓ View rendered successfully\n";
} catch (Exception $e) {
    echo "ERROR during rendering: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
