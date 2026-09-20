<?php
$res = @include "../../main.inc.php";
if (!$res) $res = @include "../../../main.inc.php";

$moduledir = 'fraispro';
$myTmpObjectKey = 'FraisproReceipt';
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
print_r($dirmodels);

$found = 0;
foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/".$moduledir);
	echo "Checking $dir\n";
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (strpos($file, 'mod_'.strtolower($myTmpObjectKey).'_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					echo "Found $file\n";
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir.'/'.$file.'.php';
					$module = new $file($db);
					echo "Loaded module ".$module->name."\n";
					$found++;
				}
			}
			closedir($handle);
		}
	}
}
if (!$found) echo "No modules found.\n";
