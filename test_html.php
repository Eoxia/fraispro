<?php
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);
define('NOREQUIRESOC', 1);
define('NOLOGIN', 1);
require_once '../../main.inc.php';
$user->id = 1; // force admin user
require 'view/frontend/traitement.php';
