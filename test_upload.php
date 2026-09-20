<?php
unset($_SERVER['argv']);
unset($_SERVER['argc']);
$_SERVER['CONTEXT_DOCUMENT_ROOT'] = 'C:/wamp64/www/dolibarr23/htdocs';
$_SERVER['SCRIPT_FILENAME'] = 'C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/view/frontend/app.php';
$_SERVER['REQUEST_URI'] = '/dolibarr23/htdocs/custom/fraispro/view/frontend/app.php?action=uploadPhoto';
$_GET['action'] = 'uploadPhoto';
$_POST['sub_dir'] = 'tmp/fast_capture_1';
$_FILES['userfile'] = [
    'name' => 'test_image.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => 'C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/test_image.jpg',
    'error' => 0,
    'size' => 15
];

file_put_contents('C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/test_image.jpg', 'fake_image_data');

require_once 'C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/view/frontend/app.php';
echo "\nDONE.\n";
