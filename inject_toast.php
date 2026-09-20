<?php
$content = file_get_contents('C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\view\frontend\app.php');
$content = preg_replace("/setEventMessages\('.*valid.*', null, 'mesgs'\);/", "setEventMessages('Reçu : ' . \$receipt->ref . ' - Transféré dans Traitement', null, 'mesgs');", $content);
file_put_contents('C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\view\frontend\app.php', $content);
echo "Replaced toast message\n";
