<?php
require '../../main.inc.php';
$resql = "SELECT rowid, ref, date_creation FROM " . MAIN_DB_PREFIX . "fraispro_receipt ORDER BY rowid DESC LIMIT 5";
$res = $db->query($resql);
while ($obj = $db->fetch_object($res)) {
    echo "ID: " . $obj->rowid . " | REF: " . $obj->ref . " | DB DATE: " . $obj->date_creation . " | jdate: " . $db->jdate($obj->date_creation) . " | formatted auto: " . dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'auto') . " | formatted tzuserrel: " . dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuserrel') . "\n";
}
