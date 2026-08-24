<?php
$html = file_get_contents(__DIR__ . '/../index.html');

// Extract all src, href, action
preg_match_all('/(?:src|href)=["\']([^"\']+)["\']/', $html, $matches);

$urls = array_unique($matches[1]);

echo "=== AUDITING ALL ASSETS IN INDEX.HTML ===\n";

foreach ($urls as $u) {
    if (strpos($u, '#') === 0 || strpos($u, 'data:') === 0) continue;

    $fullUrl = (strpos($u, 'http') === 0) ? $u : "http://localhost:8000/" . ltrim($u, '/');

    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Asset: '$u' -> HTTP $code\n";
}
