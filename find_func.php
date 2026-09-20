<?php
$dir = new RecursiveDirectoryIterator('C:\wamp64\www\dolibarr23\htdocs\custom');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.php$/', RegexIterator::GET_MATCH);
foreach($files as $file) {
    $content = file_get_contents($file[0]);
    if (strpos($content, 'function saturne_render_media_block') !== false) {
        echo "Found in " . $file[0] . "\n";
    }
}
