<?php
$content = file_get_contents('C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\view\frontend\app.php');
$search = <<<'EOD'
                $allFiles = dol_dir_list($dir, 'files', 0, '', '(?i)\.meta$', 'date', SORT_DESC);
                if (!empty($allFiles)) {
                    $firstFileObj = $allFiles[0];
                    $firstFile = $firstFileObj['name'];
                    $fileSize = isset($firstFileObj['size']) ? (int)$firstFileObj['size'] : 0;
                    if ($fileSize == 0 && file_exists($dir . '/' . $firstFile)) {
                        $fileSize = (int)filesize($dir . '/' . $firstFile);
                    }
                    $fileSizeKo = round($fileSize / 1024);
                    $fileTitleInfo = ' - ' . $firstFile . ' (' . $fileSizeKo . 'ko)';
                }
                
                $imageFiles = dol_dir_list($dir, 'files', 0, '\.(png|jpg|jpeg|gif|webp)$', '(?i)\.meta$', 'date', SORT_DESC);
                if (!empty($imageFiles)) {
                    $thumbUrl = DOL_URL_ROOT . '/document.php?modulepart=fraispro&entity=1&file=' . urlencode(dol_sanitizeFileName($ref) . '/' . $imageFiles[0]['name']);
                }
EOD;

$replace = <<<'EOD'
                $imageFiles = dol_dir_list($dir, 'files', 0, '\.(png|jpg|jpeg|gif|webp)$', '(?i)\.meta$', 'date', SORT_DESC);
                $allFiles = dol_dir_list($dir, 'files', 0, '', '(?i)\.meta$', 'date', SORT_DESC);
                
                $titleFileObj = null;
                if (!empty($imageFiles)) {
                    $titleFileObj = $imageFiles[0];
                    $thumbUrl = DOL_URL_ROOT . '/document.php?modulepart=fraispro&entity=1&file=' . urlencode(dol_sanitizeFileName($ref) . '/' . $titleFileObj['name']);
                } elseif (!empty($allFiles)) {
                    $titleFileObj = $allFiles[0];
                }
                
                if ($titleFileObj) {
                    $firstFile = $titleFileObj['name'];
                    $fileSize = isset($titleFileObj['size']) ? (int)$titleFileObj['size'] : 0;
                    if ($fileSize == 0 && file_exists($dir . '/' . $firstFile)) {
                        $fileSize = (int)filesize($dir . '/' . $firstFile);
                    }
                    $fileSizeKo = round($fileSize / 1024);
                    $fileTitleInfo = ' - ' . $firstFile . ' (' . $fileSizeKo . 'ko)';
                }
EOD;

$content = str_replace($search, $replace, $content);
file_put_contents('C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\view\frontend\app.php', $content);
echo "Replaced title logic\n";
