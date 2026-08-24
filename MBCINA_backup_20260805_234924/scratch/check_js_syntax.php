<?php
$js = file_get_contents('c:/xampp/htdocs/MBCINA/js/app.js');
$openBraces = substr_count($js, '{');
$closeBraces = substr_count($js, '}');
echo "OPEN BRACES: $openBraces, CLOSE BRACES: $closeBraces\n";
