<?php
$file = 'resources/views/tickets/print.blade.php';
$content = file_get_contents($file);
$content = preg_replace('/ h-\[\d+px\]/', '', $content);
file_put_contents($file, $content);
