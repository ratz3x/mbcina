<?php
$lineJson = file_get_contents(__DIR__ . '/line_m5_1861.json');
$data = json_decode($lineJson, true);

echo "Processing line 1861...\n";
if (isset($data['tool_calls'])) {
    foreach ($data['tool_calls'] as $tc) {
        if (isset($tc['args']['CodeContent'])) {
            echo "Found CodeContent! len: " . strlen($tc['args']['CodeContent']) . "\n";
            file_put_contents(__DIR__ . '/../index.html', $tc['args']['CodeContent']);
            echo "✔ Wrote CodeContent to index.html!\n";
        }
    }
}
