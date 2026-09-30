<?php

$files = [
    'resources/views/tickets/show.blade.php',
    'resources/views/layouts/navigation.blade.php',
    'resources/views/dashboard-user.blade.php',
    'resources/views/case-detail.blade.php',
    'resources/views/audit_logs/index.blade.php',
];
foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace("->format('d/M/Y'", "->translatedFormat('d F Y'", $content);
    $content = str_replace("->format('d/M/Y ", "->translatedFormat('d F Y ", $content);
    file_put_contents($file, $content);
}
echo "Done\n";
