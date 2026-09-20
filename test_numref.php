<?php
require '../../main.inc.php';
$receipt = new FraisproReceipt($db);
$num = $receipt->getNextNumRef();
var_dump($num);
var_dump($receipt->error);
