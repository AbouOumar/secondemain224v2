<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$articles = DB::table('articles')->get();
foreach($articles as $article) {
    echo 'ID: ' . $article->id . ', Currency: [' . $article->currency . ']' . PHP_EOL;
}
?>