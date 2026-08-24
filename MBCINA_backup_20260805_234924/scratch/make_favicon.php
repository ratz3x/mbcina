<?php
$source = __DIR__ . '/../assets/mb_badge.jpg';
$dest = __DIR__ . '/../favicon.ico';

if (file_exists($source)) {
    copy($source, $dest);
    echo "FAVICON.ICO CREATED SUCCESSFULLY FROM ASSETS/MB_BADGE.JPG!\n";
} else {
    echo "SOURCE ASSET NOT FOUND!\n";
}
