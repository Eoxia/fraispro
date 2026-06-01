<?php
/* Copyright (C) 2026 Evarisk <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/custom/fraispro/fraispro_list.php
 *      \ingroup    fraispro
 *      \brief      List page for expense reports (using native Dolibarr tables)
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

// Load translation files
$langs->loadLangs(array("fraispro@fraispro", "trips", "other"));

// Get parameters
$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'fraispro_list';
$optioncss  = GETPOST('optioncss', 'aZ');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Default sort order
if (!$sortfield) {
	$sortfield = "er.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

// Search filters
$search_ref = GETPOST('search_ref', 'alpha');
$search_user = GETPOST('search_user', 'alpha');
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));
$search_status = GETPOST('search_status', 'intcomma');

// Clear filters
if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {
	$search_ref = '';
	$search_user = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_status = '';
}

// Security Check
if (!isModEnabled("fraispro")) {
	accessforbidden('Module Fraispro not enabled');
}

$title = $langs->trans("NoteDeFrais");

/*
 * Build and execute SQL Select
 */
$sql = "SELECT er.rowid, er.ref, er.date_debut, er.date_fin, er.total_ht, er.total_tva, er.total_ttc, er.fk_statut as status, er.paid,";
$sql .= " er.fk_user_author, er.date_create,";
$sql .= " u.lastname, u.firstname, u.login, u.photo";
$sql .= " FROM ".$db->prefix()."expensereport as er";
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = er.fk_user_author";
$sql .= " WHERE er.entity IN (".getEntity('expensereport').")";

if ($search_ref) {
	$sql .= natural_search('er.ref', $search_ref);
}
if ($search_user) {
	$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_user);
}
if ($search_date_start) {
	$sql .= " AND er.date_debut >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND er.date_fin <= '".$db->idate($search_date_end)."'";
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND er.fk_statut = ".((int) $search_status);
}

// Count total number of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$sqlforcount = "SELECT COUNT(er.rowid) as nbtotalofrecords FROM ".$db->prefix()."expensereport as er";
	$sqlforcount .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = er.fk_user_author";
	$sqlforcount .= " WHERE er.entity IN (".getEntity('expensereport').")";

	if ($search_ref) {
		$sqlforcount .= natural_search('er.ref', $search_ref);
	}
	if ($search_user) {
		$sqlforcount .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_user);
	}
	if ($search_date_start) {
		$sqlforcount .= " AND er.date_debut >= '".$db->idate($search_date_start)."'";
	}
	if ($search_date_end) {
		$sqlforcount .= " AND er.date_fin <= '".$db->idate($search_date_end)."'";
	}
	if ($search_status != '' && $search_status >= 0) {
		$sqlforcount .= " AND er.fk_statut = ".((int) $search_status);
	}

	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}
	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Output page
$form = new Form($db);

llxHeader('', $title, '', '', 0, 0, array(), array(), '', 'mod-fraispro page-list bodyforlist');

$param = '';
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_user) {
	$param .= '&search_user='.urlencode($search_user);
}
if ($search_status != '' && $search_status >= 0) {
	$param .= '&search_status='.urlencode($search_status);
}
if ($search_date_start) {
	$param .= '&search_date_startmonth='.GETPOSTINT('search_date_startmonth').'&search_date_startday='.GETPOSTINT('search_date_startday').'&search_date_startyear='.GETPOSTINT('search_date_startyear');
}
if ($search_date_end) {
	$param .= '&search_date_endmonth='.GETPOSTINT('search_date_endmonth').'&search_date_endday='.GETPOSTINT('search_date_endday').'&search_date_endyear='.GETPOSTINT('search_date_endyear');
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'trip', 0, '', '', $limit, 0, 0, 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal noborder liste">'."\n";

// Status options for filter
$statusOptions = array(
	'' => '',
	'0' => $langs->trans('Draft'),
	'2' => $langs->trans('ValidatedWaitingApproval'),
	'5' => $langs->trans('Approved'),
	'6' => $langs->trans('Paid'),
	'4' => $langs->trans('Canceled'),
	'99' => $langs->trans('Refused'),
);

// Fields title search
print '<tr class="liste_titre_filter">';
// Ref
print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
// User
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_user" value="'.dol_escape_htmltag($search_user).'"></td>';
// Date début
print '<td class="liste_titre center">';
print '<div class="nowrap">';
print $form->selectDate($search_date_start ? $search_date_start : '', "search_date_start", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
print '</div>';
print '<div class="nowrap">';
print $form->selectDate($search_date_end ? $search_date_end : '', "search_date_end", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
print '</div>';
print '</td>';
// Date fin
print '<td class="liste_titre"></td>';
// Total HT
print '<td class="liste_titre"></td>';
// Total TVA
print '<td class="liste_titre"></td>';
// Total TTC
print '<td class="liste_titre"></td>';
// Status
print '<td class="liste_titre center">';
print $form->selectarray('search_status', $statusOptions, $search_status, 0, 0, 0, '', 1, 0, 0, '', 'maxwidth100 onrightofpage');
print '</td>';
// Actions filter buttons
print '<td class="liste_titre center maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";

// Fields title label
print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans("Ref"), 0, $_SERVER['PHP_SELF'], 'er.ref', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("User"), 0, $_SERVER['PHP_SELF'], 'u.lastname', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("DateStart"), 0, $_SERVER['PHP_SELF'], 'er.date_debut', '', $param, 'class="center"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("DateEnd"), 0, $_SERVER['PHP_SELF'], 'er.date_fin', '', $param, 'class="center"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("AmountHT"), 0, $_SERVER['PHP_SELF'], 'er.total_ht', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("AmountVAT"), 0, $_SERVER['PHP_SELF'], 'er.total_tva', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("AmountTTC"), 0, $_SERVER['PHP_SELF'], 'er.total_ttc', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], 'er.fk_statut', '', $param, 'class="center"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder)."\n";
print '</tr>'."\n";

// Loop on records
$i = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
$expensereportstatic = new ExpenseReport($db);

while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break;
	}

	$expensereportstatic->id = $obj->rowid;
	$expensereportstatic->ref = $obj->ref;
	$expensereportstatic->status = $obj->status;
	$expensereportstatic->fk_statut = $obj->status;

	print '<tr class="oddeven">';

	// Ref
	print '<td class="nowrap">';
	print $expensereportstatic->getNomUrl(1);
	print '</td>';

	// User
	print '<td class="nowrap">';
	if ($obj->fk_user_author > 0) {
		$usertmp = new User($db);
		$usertmp->id = $obj->fk_user_author;
		$usertmp->lastname = $obj->lastname;
		$usertmp->firstname = $obj->firstname;
		$usertmp->login = $obj->login;
		$usertmp->photo = $obj->photo;
		print $usertmp->getNomUrl(-1);
	}
	print '</td>';

	// Date début
	print '<td class="center">'.dol_print_date($db->jdate($obj->date_debut), 'day').'</td>';

	// Date fin
	print '<td class="center">'.dol_print_date($db->jdate($obj->date_fin), 'day').'</td>';

	// Total HT
	print '<td class="right nowrap">'.price($obj->total_ht).'</td>';

	// Total TVA
	print '<td class="right nowrap">'.price($obj->total_tva).'</td>';

	// Total TTC
	print '<td class="right nowrap">'.price($obj->total_ttc).'</td>';

	// Status
	print '<td class="center">'.$expensereportstatic->getLibStatut(5).'</td>';

	// Actions column (placeholder)
	print '<td></td>';

	print '</tr>'."\n";
	$i++;
}

$db->free($resql);

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
