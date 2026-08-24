<?php
// Check if there are any duplicate IDs or unclosed tags in index.html
$html = file_get_contents(__DIR__ . '/../index.html');

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($html);

$errors = libxml_get_errors();
echo "Total HTML parser errors/warnings: " . count($errors) . "\n";
foreach (array_slice($errors, 0, 10) as $err) {
    echo " -> Line {$err->line}: {$err->message}";
}
libxml_clear_errors();
