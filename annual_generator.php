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

$langs->loadLangs(array("main", "fraispro@fraispro", "trips", "users"));

// Check access
if (empty($user->rights->expensereport->creer)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$target_user_id = GETPOST('target_user_id', 'int');
if (empty($target_user_id)) {
	$target_user_id = $user->id; // Default to current user for the main button
}

$year = dol_print_date(dol_now(), '%Y');

if ($action == 'generate' && !empty($target_user_id)) {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

	// Check where to start
	$start_month = 1;
	$sql = "SELECT MAX(date_fin) as last_date FROM ".$db->prefix()."expensereport ";
	$sql .= "WHERE fk_user_author = ".((int)$target_user_id)." ";
	$sql .= "AND date_fin >= '".$db->idate(dol_mktime(0,0,0,1,1,$year))."' ";
	$sql .= "AND date_fin <= '".$db->idate(dol_mktime(23,59,59,12,31,$year))."'";
	
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->last_date)) {
			$last_date = $db->jdate($obj->last_date);
			$start_month = (int) dol_print_date($last_date, '%m') + 1;
		}
	}

	if ($start_month <= 12) {
		$error = 0;
		$created = 0;
		$db->begin();

		for ($month = $start_month; $month <= 12; $month++) {
			$date_debut = dol_mktime(0, 0, 0, $month, 1, $year);
			$date_fin = dol_get_last_day($year, $month);

			$er = new ExpenseReport($db);
			$er->date_debut = $date_debut;
			$er->date_fin = $date_fin;
			$er->fk_user_author = $target_user_id;
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
			setEventMessages($langs->trans("FraisproMissingNDFGenerated", $created), null, 'mesgs');
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$db->rollback();
		}
	} else {
		setEventMessages($langs->trans("FraisproNoNDFToGenerate"), null, 'warnings');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

llxHeader('', $langs->trans("FraisproAnnualGenerator"));

print load_fiche_titre($langs->trans("FraisproAnnualGenerator"));

print '<div class="info">Cet outil permet de générer automatiquement en brouillon les notes de frais manquantes de l\'année courante (<strong>' . $year . '</strong>), avec les bonnes dates de début et de fin pour chaque mois.</div>';

print '<div class="center" style="margin-top: 20px; margin-bottom: 20px;">';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="generate">';
print '<button type="submit" class="button">' . $langs->trans("FraisproGenerateYearlyBtn") . '</button>';
print '</form>';
print '</div>';

// Table of employees
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("FraisproEmployeeList").'</td>';
print '<td>'.$langs->trans("FraisproLastNDFDate").'</td>';
print '<td class="center">'.$langs->trans("FraisproNDFToGenerate").'</td>';
print '<td class="right"></td>';
print '</tr>';

$sql = "SELECT u.rowid, u.firstname, u.lastname, u.login, u.statut, u.photo, MAX(e.date_fin) as last_date";
$sql.= " FROM " . $db->prefix() . "user as u";
$sql.= " LEFT JOIN " . $db->prefix() . "expensereport as e ON e.fk_user_author = u.rowid ";
$sql.= " AND e.date_fin >= '".$db->idate(dol_mktime(0,0,0,1,1,$year))."' AND e.date_fin <= '".$db->idate(dol_mktime(23,59,59,12,31,$year))."'";
$sql.= " WHERE u.statut = 1 AND u.employee = 1"; // Active employees
$sql.= " GROUP BY u.rowid, u.firstname, u.lastname, u.login, u.statut, u.photo";
$sql.= " ORDER BY u.lastname, u.firstname";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	$userstatic = new User($db);
	
	if ($num > 0) {
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			
			$userstatic->id = $obj->rowid;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			$userstatic->statut = $obj->statut;
			$userstatic->photo = $obj->photo;
			
			$last_date = $db->jdate($obj->last_date);
			$start_month = 1;
			if (!empty($last_date)) {
				$start_month = (int) dol_print_date($last_date, '%m') + 1;
			}
			
			$missing = 12 - $start_month + 1;
			if ($missing < 0) $missing = 0;

			print '<tr class="oddeven">';
			print '<td>'.$userstatic->getNomUrl(-1).'</td>';
			print '<td>'.($last_date ? dol_print_date($last_date, 'day') : '<span class="opacitymedium">Aucune pour '.$year.'</span>').'</td>';
			print '<td class="center">'.$missing.'</td>';
			print '<td class="right">';
			if ($missing > 0) {
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=generate&target_user_id='.$obj->rowid.'&token='.newToken().'" class="button">'.$langs->trans("FraisproGenerateAction").'</a>';
			} else {
				print '<span class="opacitymedium">Terminé</span>';
			}
			print '</td>';
			print '</tr>';
			
			$i++;
		}
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">Aucun salarié actif trouvé.</span></td></tr>';
	}
} else {
	dol_print_error($db);
}
print '</table>';

llxFooter();
$db->close();
