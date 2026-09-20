<?php
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);
define('NOREQUIRESOC', 1);
define('NOLOGIN', 1);

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once 'class/fraispro_receipt.class.php';

$sql = "SELECT rowid, ref, status, date_creation, fk_expensereport, fk_user_creat FROM " . MAIN_DB_PREFIX . "fraispro_receipt WHERE status = 1";
$resql = $db->query($sql);
if ($resql) {
    echo "Status 1 receipts: " . $db->num_rows($resql) . "\n";
    while ($obj = $db->fetch_object($resql)) {
        echo "Rowid: " . $obj->rowid . ", Ref: " . $obj->ref . ", fk_user_creat: " . $obj->fk_user_creat . "\n";
    }
}
