<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select('PRAGMA table_info(articles)');
echo "Articles table structure:\n";
foreach($columns as $col) {
    echo "- {$col->name} ({$col->type})\n";
    if($col->name == 'statut') {
        echo "  ^^^ FOUND STATUT COLUMN ^^^\n";
    }
}
?>