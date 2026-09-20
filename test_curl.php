<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/dolibarr23/htdocs/custom/fraispro/view/frontend/app.php?action=uploadPhoto");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);

// Create a CURLFile object for the file
$cfile = new CURLFile('C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/test_image.jpg', 'image/jpeg', 'test_image.jpg');

$data = [
    'sub_dir' => 'tmp/fast_capture_1',
    'module_name' => 'fraispro',
    'userfile' => $cfile,
];

curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo "Response:\n" . $result;
}
curl_close($ch);
