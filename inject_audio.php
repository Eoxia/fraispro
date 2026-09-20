<?php
$content = file_get_contents('C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\view\frontend\app.php');
$search = <<<'EOD'
} elseif ($action == 'delete_receipt') {
EOD;

$replace = <<<'EOD'
} elseif ($action == 'add_audio' && !empty($_POST['module_name']) && !empty($_FILES['audio']['tmp_name'])) {
    $saturneModule = GETPOST('module_name', 'alpha');
    $saturneSubDir = GETPOST('sub_dir', 'alpha');
    $audioModLower = dol_strtolower($saturneModule);

    $uploadDir = !empty($conf->$audioModLower->dir_output)
        ? $conf->$audioModLower->dir_output
        : $conf->ecm->dir_output . '/' . $audioModLower;
    if (!empty($saturneSubDir)) {
        $uploadDir .= '/' . $saturneSubDir;
    }

    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    $fileName = dol_print_date(dol_now(), 'dayhourlog') . '_audio.wav';
    $destFile = $uploadDir . '/' . $fileName;
    
    dol_move_uploaded_file($_FILES['audio']['tmp_name'], $destFile, 1, 0, $_FILES['audio']['error']);
    // No exit here, let view render so Saturne JS parses it
} elseif ($action == 'delete_audio' && !empty($_POST['module_name'])) {
    $saturneModule = GETPOST('module_name', 'alpha');
    $saturneSubDir = GETPOST('sub_dir', 'alpha');
    $audioFilename = GETPOST('filename', 'alpha');
    $audioModLower = dol_strtolower($saturneModule);

    $uploadDir = !empty($conf->$audioModLower->dir_output)
        ? $conf->$audioModLower->dir_output
        : $conf->ecm->dir_output . '/' . $audioModLower;
    if (!empty($saturneSubDir)) {
        $uploadDir .= '/' . $saturneSubDir;
    }

    $filePath = $uploadDir . '/' . basename($audioFilename);
    if (!empty($audioFilename) && file_exists($filePath)) {
        dol_delete_file($filePath);
    }
    // No exit here, let view render so Saturne JS parses it
} elseif ($action == 'delete_receipt') {
EOD;

$content = str_replace($search, $replace, $content);
file_put_contents('C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\view\frontend\app.php', $content);
echo "Replaced successfully\n";
