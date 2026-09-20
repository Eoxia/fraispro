<?php
require '../../master.inc.php';
$db->query("UPDATE " . MAIN_DB_PREFIX . "fraispro_receipt SET status = 0");
echo "Restored list!\n";
