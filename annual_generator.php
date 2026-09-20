<?php
/**
 * Fraispro annual expense reports generator
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

$langs->loadLangs(array("main", "fraispro@fraispro", "trips"));

// Check access
// User must have write permission on expensereport to create them
if (empty($user->rights->expensereport->creer)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

$year = dol_print_date(dol_now(), '%Y');

if ($action == 'generate') {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

	$error = 0;
	$created = 0;

	$db->begin();

	for ($month = 1; $month <= 12; $month++) {
		// Calculate dates
		$date_debut = dol_mktime(0, 0, 0, $month, 1, $year);
		$date_fin = dol_get_last_day($year, $month);

		$er = new ExpenseReport($db);
		$er->date_debut = $date_debut;
		$er->date_fin = $date_fin;
		$er->fk_user_author = $user->id;
		$er->status = 0; // Draft

		$res = $er->create($user);
		if ($res <= 0) {
			setEventMessages($er->error, $er->errors, 'errors');
			$error++;
			break;
		}
		$created++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("FraisproGenerateYearlySuccess", $created), null, 'mesgs');
		
		// Redirect to the list
		header("Location: " . DOL_URL_ROOT . "/expensereport/list.php");
		exit;
	} else {
		$db->rollback();
	}
}

llxHeader('', $langs->trans("FraisproAnnualGenerator"));

print load_fiche_titre($langs->trans("FraisproAnnualGenerator"));

print '<div class="info">Cet outil permet de générer automatiquement en brouillon les 12 notes de frais de l\'année courante (<strong>' . $year . '</strong>) pour votre utilisateur, avec les bonnes dates de début et de fin pour chaque mois.</div>';

print '<div class="center" style="margin-top: 30px;">';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="generate">';
print '<button type="submit" class="button">' . $langs->trans("FraisproGenerateYearlyBtn") . '</button>';
print '</form>';
print '</div>';

llxFooter();
$db->close();
