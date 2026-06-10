<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$articles = DB::table('articles')->select('id', 'titre', 'etat', 'is_published', 'is_verified')->get();
echo "Article etat values:\n";
foreach($articles as $article) {
    echo "ID: {$article->id}, Titre: {$article->titre}, Etat: [{$article->etat}], Is published: [{$article->is_published ? 'yes' : 'no'}], Is verified: [{$article->is_verified ? 'yes' : 'no'}]\n";
}
?>