<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$articles = DB::table('articles')->get();
foreach($articles as $article) {
    echo 'ID: ' . $article->id . ', Etat: [' . $article->etat . '], Is_published: [' . ($article->is_published ? 'yes' : 'no') . '], Is_verified: [' . ($article->is_verified ? 'yes' : 'no') . ']' . PHP_EOL;
}
?>