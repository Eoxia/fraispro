<?php
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOLOGIN', 1);
require_once '../../main.inc.php';

$sql = "ALTER TABLE " . MAIN_DB_PREFIX . "fraispro_receipt ADD COLUMN description text";
$resql = $db->query($sql);
if ($resql) echo "Added description column\n";
else echo "Failed to add description column (maybe it exists?)\n";

$sql = "ALTER TABLE " . MAIN_DB_PREFIX . "fraispro_receipt ADD COLUMN fk_project integer";
$resql = $db->query($sql);
if ($resql) echo "Added fk_project column\n";
else echo "Failed to add fk_project column (maybe it exists?)\n";
