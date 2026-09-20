<?php
require '../../master.inc.php';
require_once 'class/fraispro_receipt.class.php';

$user->fetch(1); // Load admin user

$resql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "fraispro_receipt WHERE status = 0 OR ref LIKE '(PROV%'";
$res = $db->query($resql);
$count = 0;
while ($obj = $db->fetch_object($res)) {
    $receipt = new FraisproReceipt($db);
    if ($receipt->fetch($obj->rowid) > 0) {
        $resval = $receipt->validate($user);
        if ($resval > 0) {
            echo "Validated receipt " . $obj->rowid . " -> " . $receipt->ref . "\n";
            $count++;
        } else {
            echo "Error validating " . $obj->rowid . ": " . $receipt->error . "\n";
        }
    }
}
echo "Validated $count receipts.\n";
