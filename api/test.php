<?php
$file = __DIR__ . '/lead.php';
$output = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');
echo $output ?: 'shell_exec deshabilitado';
