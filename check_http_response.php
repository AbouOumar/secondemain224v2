<?php
$html = file_get_contents('http://127.0.0.1:8000/');
$articleCount = substr_count($html, 'article-item');
$spinnerCount = substr_count($html, 'spinner-border');

echo "Articles in HTTP response: $articleCount\n";
echo "Spinners in HTTP response: $spinnerCount\n";

// Look for the articlesGrid section
$pos = strpos($html, 'id="articlesGrid"');
if ($pos !== false) {
    $section = substr($html, $pos, 1000);
    echo "\nArticlesGrid content (first 500 chars):\n";
    echo htmlspecialchars(substr($section, 0, 500)) . "\n";
}
