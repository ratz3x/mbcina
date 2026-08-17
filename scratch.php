<?php
$lines = file('c:/xampp/htdocs/MBCINA/api.php');
foreach($lines as $i => $line) {
    if(strpos($line, 'ensureM8Tables') !== false) {
        echo ($i + 1) . ": " . $line;
    }
}
