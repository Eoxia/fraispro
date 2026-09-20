<?php
/**
 * Fraispro wrapper for expense report creation
 * Redirects to the core module or shows an error if not enabled
 */

$res = 0;
if (!($inc = @include '../main.inc.php')) {
	if (!($inc = @include '../../main.inc.php')) {
		if (!($inc = @include '../../../main.inc.php')) {
			if (!($inc = @include '../../../../main.inc.php')) {
				die("main.inc.php not found");
			}
		}
	}
}

$langs->loadLangs(array("main", "fraispro@fraispro"));

// Check if core expense report module is enabled
if (empty($conf->expensereport->enabled)) {
	llxHeader('', $langs->trans("NewNoteDeFrais"));
	
	print load_fiche_titre($langs->trans("NewNoteDeFrais"));
	
	print '<div class="error">' . $langs->trans("FraisproErrorExpenseReportMustBeEnabledByAdmin") . '</div>';
	
	llxFooter();
	$db->close();
	exit;
}

// Redirect to core module
header("Location: " . DOL_URL_ROOT . "/expensereport/card.php?action=create&leftmenu=expensereport&mainmenu=hrm");
exit;
