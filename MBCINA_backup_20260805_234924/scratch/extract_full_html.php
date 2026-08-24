<?php
$lineJson = file_get_contents(__DIR__ . '/line_542.json');
$data = json_decode($lineJson, true);

if (isset($data['content'])) {
    $content = $data['content'];
    // Look for <!DOCTYPE html> ... </html>
    if (preg_match('/<!DOCTYPE html>.*<\/html>/s', $content, $m)) {
        file_put_contents(__DIR__ . '/../index.html', $m[0]);
        echo "✔ Successfully restored complete index.html from line 542 of transcript!\n";
        exit;
    }
}

// Check tool_calls
if (isset($data['tool_calls'])) {
    foreach ($data['tool_calls'] as $tc) {
        if (isset($tc['args']['CodeContent']) && strpos($tc['args']['CodeContent'], '<!DOCTYPE html>') !== false) {
            file_put_contents(__DIR__ . '/../index.html', $tc['args']['CodeContent']);
            echo "✔ Successfully restored complete index.html from tool_calls!\n";
            exit;
        }
    }
}

echo "Check failed on 542, trying line 517...\n";
$lineJson = file_get_contents(__DIR__ . '/line_517.json');
$data = json_decode($lineJson, true);
if (isset($data['tool_calls'])) {
    foreach ($data['tool_calls'] as $tc) {
        if (isset($tc['args']['CodeContent']) && strpos($tc['args']['CodeContent'], '<!DOCTYPE html>') !== false) {
            file_put_contents(__DIR__ . '/../index.html', $tc['args']['CodeContent']);
            echo "✔ Successfully restored complete index.html from line 517 tool_calls!\n";
            exit;
        }
    }
}
