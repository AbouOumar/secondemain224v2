<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$publishedCount = DB::table('articles')->where('is_published', 1)->count();
echo 'Published articles: ' . $publishedCount . PHP_EOL;
$totalCount = DB::table('articles')->count();
echo 'Total articles: ' . $totalCount . PHP_EOL;
?>