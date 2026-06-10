<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Controllers\Web\HomeController;
use Illuminate\Http\Request;

$controller = new HomeController();
$request = new Request();
$response = $controller->index();

echo "Response type: " . get_class($response) . "\n";
if (method_exists($response, 'getData')) {
    $data = $response->getData();
    echo "Featured Articles Count: " . (isset($data['featuredArticles']) ? $data['featuredArticles']->count() : 'null') . "\n";
}
?>
