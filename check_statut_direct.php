<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Try to select statut specifically
    $result = DB::select("SELECT statut FROM articles LIMIT 1");
    echo "Statut column exists and has values:\n";
    foreach($result as $row) {
        echo "Statut: '" . $row->statut . "'\n";
    }
} catch (\Exception $e) {
    echo "Error selecting statut: " . $e->getMessage() . "\n";
}

// Let's check what's actually in the table by selecting all columns for one row
try {
    $row = DB::select("SELECT * FROM articles LIMIT 1")->get()[0];
    echo "\nFirst article data:\n";
    foreach($row as $key => $value) {
        echo "$key: $value\n";
    }
} catch (\Exception $e) {
    echo "Error selecting all columns: " . $e->getMessage() . "\n";
}
?>