<?php
require '../../main.inc.php';
echo "tzserver: " . dol_print_date(time(), 'dayhour', 'tzserver') . "\n";
echo "tzuser: " . dol_print_date(time(), 'dayhour', 'tzuser') . "\n";
echo "tzuserrel: " . dol_print_date(time(), 'dayhour', 'tzuserrel') . "\n";
echo "auto: " . dol_print_date(time(), 'dayhour', 'auto') . "\n";
