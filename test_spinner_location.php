<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\Web\HomeController();
$response = $controller->index();
$html = $response->render();

// Find the position of the spinner
$spinnerPos = strpos($html, 'spinner-border');
if ($spinnerPos !== false) {
    // Get context around the spinner
    $start = max(0, $spinnerPos - 200);
    $end = min(strlen($html), $spinnerPos + 200);
    $context = substr($html, $start, $end - $start);
    
    echo "Spinner context:\n";
    echo "---\n";
    echo htmlspecialchars($context) . "\n";
    echo "---\n";
}

// Also find and display the articles section
$articlesGridPos = strpos($html, "id=\"articlesGrid\"");
if ($articlesGridPos !== false) {
    $start = $articlesGridPos;
    $end = min(strlen($html), $articlesGridPos + 500);
    $context = substr($html, $start, $end - $start);
    
    echo "\n\nArticles grid context:\n";
    echo "---\n";
    echo htmlspecialchars($context) . "\n";
    echo "---\n";
}
