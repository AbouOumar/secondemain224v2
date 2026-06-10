<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Controllers\Web\HomeController;
use Illuminate\Http\Request;

$controller = new HomeController();
$response = $controller->index();

$html = $response->render();

// Count article-item divs
preg_match_all('/class="[^"]*article-item[^"]*"/', $html, $matches);
echo "article-item count in rendered HTML: " . count($matches[0]) . "\n";

// Check for spinner
preg_match_all('/spinner-border/', $html, $spinners);
echo "Spinners count in rendered HTML: " . count($spinners[0]) . "\n";

// Show first 2000 chars to inspect
echo "\n=== First 3000 chars of rendered HTML ===\n";
echo substr($html, 0, 3000) . "\n";
?>
