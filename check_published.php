<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;

$count = Article::where('is_published', true)->count();
echo 'Published articles: ' . $count . PHP_EOL;
?>