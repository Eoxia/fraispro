<?php
require '../../master.inc.php';
$resql = 'SELECT rowid, fk_user_creat, ref FROM ' . MAIN_DB_PREFIX . 'fraispro_receipt ORDER BY rowid DESC LIMIT 10';
$res = $db->query($resql);
while ($obj = $db->fetch_object($res)) {
    echo "ID: " . $obj->rowid . " | USER: " . $obj->fk_user_creat . " | REF: " . $obj->ref . "\n";
}
