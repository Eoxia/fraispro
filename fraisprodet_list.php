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
 *      \file       htdocs/custom/fraispro/fraisprodet_list.php
 *      \ingroup    fraispro
 *      \brief      List page for expense report lines with invoice linking
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
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once __DIR__.'/class/fraispro_link.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files
$langs->loadLangs(array("fraispro@fraispro", "trips", "bills", "other", "projects"));

// Get parameters
$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$massaction = GETPOST('massaction', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'fraisprodet_list';
$toselect   = GETPOST('toselect', 'array:int');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;

// Default sort order
if (!$sortfield) {
	$sortfield = "d.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

// Search filters
$search_ref = GETPOST('search_ref', 'alpha');
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));
$search_project = GETPOST('search_project', 'alpha');
$search_type = GETPOST('search_type', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_linked = GETPOST('search_linked', 'alpha');

// Invoice selector parameters
$selected_invoice_id = GETPOSTINT('selected_invoice_id');
$markup_type = GETPOST('markup_type', 'alpha'); // 'fixed' or 'percent'
$markup_value = GETPOST('markup_value', 'alpha');

// Clear filters
if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {
	$search_ref = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_project = '';
	$search_type = '';
	$search_desc = '';
	$search_linked = '';
}

// Security Check
if (!isModEnabled("fraispro")) {
	accessforbidden('Module Fraispro not enabled');
}


/*
 * Actions
 */

$error = 0;

// Mass action: add selected expense lines to an invoice
if ($action == 'addtoinvoice' && !empty($toselect) && $selected_invoice_id > 0) {
	$billing_product_id = GETPOSTINT('billing_product_id');
	if ($billing_product_id <= 0) {
		setEventMessages($langs->trans("FraisproConfigureDefaultProductToBill"), null, 'errors');
		$action = 'view';
	} else {
		$facture = new Facture($db);
		$result = $facture->fetch($selected_invoice_id);

		if ($result > 0 && $facture->status == Facture::STATUS_DRAFT) {
			$db->begin();

			// Fetch the product type (0=product, 1=service)
			$product_type = 1;
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$prod_static = new Product($db);
			if ($prod_static->fetch($billing_product_id) > 0) {
				$product_type = $prod_static->type;
			}

			$markup_val = price2num($markup_value);
			$nb_added = 0;

			foreach ($toselect as $expdet_id) {
				// Fetch the expense line details
				$sqldet = "SELECT d.rowid, d.comments, d.qty, d.subprice, d.total_ht, d.total_ttc, d.total_tva, d.tva_tx, d.fk_facture, fd.rowid as facturedet_id";
				$sqldet .= " FROM ".$db->prefix()."expensereport_det as d";
				$sqldet .= " LEFT JOIN ".$db->prefix()."facturedet as fd ON fd.rowid = d.fk_facture";
				$sqldet .= " WHERE d.rowid = ".((int) $expdet_id);

				$resdet = $db->query($sqldet);
				if (!$resdet) {
					$error++;
					break;
				}
				$expline = $db->fetch_object($resdet);
				if (!$expline) {
					continue;
				}

				// Skip already linked lines
				if ($expline->fk_facture > 0 && !empty($expline->facturedet_id)) {
					continue;
				}

				// Calculate the unit price with optional markup
				$unit_price_ttc = (float) $expline->total_ttc;
				if ($markup_type == 'fixed' && $markup_val > 0) {
					$unit_price_ttc += $markup_val;
				} elseif ($markup_type == 'percent' && $markup_val > 0) {
					$unit_price_ttc += ($unit_price_ttc * $markup_val / 100);
				}

				// Add a line to the invoice (using TTC price, qty=1)
				$desc = $expline->comments;
				$tva_tx = (float) $expline->tva_tx;
				// Calculate HT from TTC
				$unit_price_ht = $unit_price_ttc / (1 + ($tva_tx / 100));

				$result_line = $facture->addline(
					$desc,              // description
					$unit_price_ht,     // pu_ht
					(float) $expline->qty, // qty
					$tva_tx,            // txtva
					0,                  // txlocaltax1
					0,                  // txlocaltax2
					$billing_product_id, // fk_product
					0,                  // remise_percent
					'',                 // date_start
					'',                 // date_end
					0,                  // ventil
					0,                  // info_bits
					0,                  // fk_remise_except
					'HT',              // price_base_type
					$unit_price_ttc,    // pu_ttc
					$product_type       // type (0=product, 1=service)
				);

				if ($result_line > 0) {
					// Link the expense line to the new invoice line
					$link_result = FraisproLink::linkToInvoiceLine($db, $expdet_id, $result_line);
					if ($link_result > 0) {
						$nb_added++;
					} else {
						$error++;
					}
				} else {
				setEventMessages($facture->error, $facture->errors, 'errors');
				$error++;
			}
		}

		if (!$error) {
			$db->commit();
			// Recalculate invoice totals
			$facture->update_price(1);
			setEventMessages($langs->trans("LinesAddedToInvoice", $nb_added, $facture->ref), null, 'mesgs');
		} else {
			$db->rollback();
			setEventMessages($langs->trans("ErrorAddingLines"), null, 'errors');
		}
	} else {
		setEventMessages($langs->trans("ErrorInvoiceNotDraft"), null, 'errors');
	}
	}

	$action = 'view';
	$toselect = array();
}

// Unlink a single expense line
if ($action == 'unlinkline') {
	$expdet_id = GETPOSTINT('expdet_id');
	if ($expdet_id > 0) {
		// Fetch the invoice status first
		$sql_check = "SELECT f.fk_statut FROM ".$db->prefix()."expensereport_det as d";
		$sql_check .= " JOIN ".$db->prefix()."facturedet as fd ON fd.rowid = d.fk_facture";
		$sql_check .= " JOIN ".$db->prefix()."facture as f ON f.rowid = fd.fk_facture";
		$sql_check .= " WHERE d.rowid = ".((int) $expdet_id);
		$res_check = $db->query($sql_check);
		$can_unlink = true;
		if ($res_check) {
			$obj_check = $db->fetch_object($res_check);
			if ($obj_check && $obj_check->fk_statut != Facture::STATUS_DRAFT) {
				$can_unlink = false;
			}
		}

		if (!$can_unlink) {
			setEventMessages($langs->trans("FraisproInvoiceClosedCannotUnlink"), null, 'errors');
		} else {
			$result = FraisproLink::unlinkFromInvoiceLine($db, $expdet_id);
			if ($result > 0) {
				setEventMessages($langs->trans("LinkRemoved"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("ErrorLinkRemoval"), null, 'errors');
			}
		}
	}
	$action = 'view';
}

// Link a single expense line to an invoice line
if ($action == 'linkline') {
	$expdet_id = GETPOSTINT('expdet_id');
	$facturedet_id = GETPOSTINT('facturedet_id');
	if ($expdet_id > 0 && $facturedet_id > 0) {
		$result = FraisproLink::linkToInvoiceLine($db, $expdet_id, $facturedet_id);
		if ($result > 0) {
			setEventMessages($langs->trans("LinkCreated"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorLinkCreation"), null, 'errors');
		}
	}
	$action = 'view';
}



$title = $langs->trans("LignesNoteDeFrais");

/*
 * Build and execute SQL Select
 */
$sql = "SELECT d.rowid, d.fk_expensereport, d.date as date_line, d.fk_projet, d.comments, d.qty, d.subprice, d.tva_tx, d.total_ht, d.total_ttc,";
$sql .= " d.fk_facture,";
$sql .= " er.ref as expensereport_ref, tf.label as type_label, tf.code as type_code,";
$sql .= " u.rowid as user_id, u.lastname, u.firstname, u.login, u.gender, u.photo, u.statut as user_statut, u.entity as user_entity,";
$sql .= " p.ref as project_ref, p.title as project_title,";
$sql .= " fd.total_ht as facturedet_ht, fd.total_ttc as facturedet_ttc, fd.description as facturedet_desc,";
$sql .= " f.ref as facture_ref, f.rowid as facture_id, f.fk_statut as facture_statut";
$sql .= " FROM ".$db->prefix()."expensereport_det as d";
$sql .= " LEFT JOIN ".$db->prefix()."expensereport as er ON er.rowid = d.fk_expensereport";
$sql .= " LEFT JOIN ".$db->prefix()."c_type_fees as tf ON tf.id = d.fk_c_type_fees";
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = er.fk_user_author";
$sql .= " LEFT JOIN ".$db->prefix()."projet as p ON p.rowid = d.fk_projet";
$sql .= " LEFT JOIN ".$db->prefix()."facturedet as fd ON fd.rowid = d.fk_facture AND d.fk_facture > 0";
$sql .= " LEFT JOIN ".$db->prefix()."facture as f ON f.rowid = fd.fk_facture";
$sql .= " WHERE er.entity IN (".getEntity('expensereport').")";

if ($search_ref) {
	$sql .= natural_search('er.ref', $search_ref);
}
if ($search_desc) {
	$sql .= natural_search('d.comments', $search_desc);
}
if ($search_project) {
	$sql .= natural_search('p.ref', $search_project);
}
if ($search_type) {
	$sql .= natural_search('tf.label', $search_type);
}
if ($search_date_start) {
	$sql .= " AND d.date >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND d.date <= '".$db->idate($search_date_end)."'";
}
if ($search_linked === '1') {
	$sql .= " AND d.fk_facture > 0";
} elseif ($search_linked === '0') {
	$sql .= " AND (d.fk_facture = 0 OR d.fk_facture IS NULL)";
}

// Count total number of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$sqlforcount = "SELECT COUNT(d.rowid) as nbtotalofrecords FROM ".$db->prefix()."expensereport_det as d";
	$sqlforcount .= " LEFT JOIN ".$db->prefix()."expensereport as er ON er.rowid = d.fk_expensereport";
	$sqlforcount .= " LEFT JOIN ".$db->prefix()."c_type_fees as tf ON tf.id = d.fk_c_type_fees";
	$sqlforcount .= " LEFT JOIN ".$db->prefix()."projet as p ON p.rowid = d.fk_projet";
	$sqlforcount .= " WHERE er.entity IN (".getEntity('expensereport').")";

	if ($search_ref) {
		$sqlforcount .= natural_search('er.ref', $search_ref);
	}
	if ($search_desc) {
		$sqlforcount .= natural_search('d.comments', $search_desc);
	}
	if ($search_project) {
		$sqlforcount .= natural_search('p.ref', $search_project);
	}
	if ($search_type) {
		$sqlforcount .= natural_search('tf.label', $search_type);
	}
	if ($search_date_start) {
		$sqlforcount .= " AND d.date >= '".$db->idate($search_date_start)."'";
	}
	if ($search_date_end) {
		$sqlforcount .= " AND d.date <= '".$db->idate($search_date_end)."'";
	}
	if ($search_linked === '1') {
		$sqlforcount .= " AND d.fk_facture > 0";
	} elseif ($search_linked === '0') {
		$sqlforcount .= " AND (d.fk_facture = 0 OR d.fk_facture IS NULL)";
	}

	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
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

// Load list of draft invoices for the selector
$sqlinv = "SELECT f.rowid, f.ref, s.nom as thirdparty_name, f.total_ttc";
$sqlinv .= " FROM ".$db->prefix()."facture as f";
$sqlinv .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = f.fk_soc";
$sqlinv .= " WHERE f.fk_statut = ".Facture::STATUS_DRAFT;
$sqlinv .= " AND f.entity IN (".getEntity('facture').")";
$sqlinv .= " ORDER BY f.ref DESC";
$resinv = $db->query($sqlinv);
$invoiceOptions = array();
if ($resinv) {
	while ($objinv = $db->fetch_object($resinv)) {
		$label = $objinv->ref;
		if ($objinv->thirdparty_name) {
			$label .= ' - '.$objinv->thirdparty_name;
		}
		$invoiceOptions[$objinv->rowid] = $label;
	}
	$db->free($resinv);
}

// Output page
$form = new Form($db);

llxHeader('', $title, '', '', 0, 0, array('/fraispro/js/modules/link.js'), array(), '', 'mod-fraispro page-list-lines bodyforlist');

// Unlink confirmation
if ($action == 'ask_unlinkline') {
	$expdet_id = GETPOSTINT('expdet_id');
	$formquestion = array();
	print $form->formconfirm($_SERVER["PHP_SELF"].'?expdet_id='.$expdet_id, $langs->trans('Unlink'), $langs->trans('ConfirmUnlink'), 'unlinkline', $formquestion, '', 1, 260);
}

$param = '';
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_desc) {
	$param .= '&search_desc='.urlencode($search_desc);
}
if ($search_project) {
	$param .= '&search_project='.urlencode($search_project);
}
if ($search_type) {
	$param .= '&search_type='.urlencode($search_type);
}
if ($search_linked !== '') {
	$param .= '&search_linked='.urlencode($search_linked);
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

// Invoice selector bar
$billing_product_id = GETPOSTINT('billing_product_id');
if ($billing_product_id <= 0) {
	$billing_product_id = getDolGlobalInt('FRAISPRO_DEFAULT_PRODUCT');
}

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">';

// Invoice selector
print '<strong>'.$langs->trans("TargetInvoice").'</strong> ';
print '<select name="selected_invoice_id" class="flat minwidth200">';
print '<option value="0">'.$langs->trans("SelectDraftInvoice").'</option>';
foreach ($invoiceOptions as $invId => $invLabel) {
	$selected = ($selected_invoice_id == $invId) ? ' selected' : '';
	print '<option value="'.$invId.'"'.$selected.'>'.dol_escape_htmltag($invLabel).'</option>';
}
print '</select>';

// Product / Service selector
print ' &nbsp; <strong>'.$langs->trans("ProductOrService").'</strong> ';
$form->select_produits($billing_product_id, 'billing_product_id', '', 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'minwidth200 flat inline-block valignmiddle');

// Markup type
print ' &nbsp; <strong>'.$langs->trans("Markup").'</strong> ';
print '<select name="markup_type" class="flat maxwidth100">';
print '<option value=""'.($markup_type == '' ? ' selected' : '').'>'.$langs->trans("None").'</option>';
print '<option value="fixed"'.($markup_type == 'fixed' ? ' selected' : '').'>'.$langs->trans("FixedAmount").'</option>';
print '<option value="percent"'.($markup_type == 'percent' ? ' selected' : '').'>'.$langs->trans("Percentage").' %</option>';
print '</select>';

// Markup value
print ' <input type="text" name="markup_value" class="flat maxwidth50" value="'.dol_escape_htmltag($markup_value).'" placeholder="0">';

// Add button
$setup_url = dol_buildpath('/fraispro/admin/setup.php', 1);
$tooltip_text = $langs->transnoentitiesnoconv("FraisproConfigureDefaultProductToBill", "", "");
$warning_html = $langs->trans("FraisproConfigureDefaultProductToBill", '<a href="' . $setup_url . '" style="color: #ea4335; text-decoration: underline;">', '</a>');

$is_product_invalid = ($billing_product_id <= 0);
$is_invoice_invalid = ($selected_invoice_id <= 0);
$is_btn_disabled = ($is_product_invalid || $is_invoice_invalid);

$btn_class = $is_btn_disabled ? 'button small butActionRefused' : 'button small';
$btn_disabled_attr = $is_btn_disabled ? ' disabled' : '';
$product_warning_style = $is_product_invalid ? 'color: #ea4335; font-weight: bold;' : 'color: #ea4335; font-weight: bold; display: none;';
$invoice_warning_style = $is_invoice_invalid ? 'color: #ea4335; font-weight: bold;' : 'color: #ea4335; font-weight: bold; display: none;';

$invoice_warning_text = $langs->trans("FraisproSelectTargetInvoiceWarning");
if ($invoice_warning_text == "FraisproSelectTargetInvoiceWarning") {
	$invoice_warning_text = "Sélectionnez une facture cible";
}

print ' <button type="submit" id="fraispro-add-to-invoice-btn" name="action" value="addtoinvoice" class="'.$btn_class.'"'.$btn_disabled_attr.'>';
print img_picto('', 'add', 'class="pictofixedwidth"');
print $langs->trans("AddToInvoice");
print '</button>';
print ' &nbsp; <span class="error fraispro-default-product-warning" style="'.$product_warning_style.'">'.$warning_html.'</span>';
print ' <span class="error fraispro-target-invoice-warning" style="'.$invoice_warning_style.'">'.$invoice_warning_text.'</span>';

print '</div>';
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal noborder liste">'."\n";

// Linked status filter options
$linkedOptions = array('' => '', '1' => $langs->trans('Linked'), '0' => $langs->trans('NotLinked'));

// Fields title search
print '<tr class="liste_titre_filter">';
// Checkbox column
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
// Reference
print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
// User
print '<td class="liste_titre"></td>';
// Line
print '<td class="liste_titre"></td>';
// Date
print '<td class="liste_titre center">';
print '<div class="nowrap">';
print $form->selectDate($search_date_start ? $search_date_start : '', "search_date_start", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
print '</div>';
print '<div class="nowrap">';
print $form->selectDate($search_date_end ? $search_date_end : '', "search_date_end", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
print '</div>';
print '</td>';
// Project
print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_project" value="'.dol_escape_htmltag($search_project).'"></td>';
// Type
print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_type" value="'.dol_escape_htmltag($search_type).'"></td>';
// Description
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
// TVA, PU HT, Qty, Total HT, Total TTC
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
// Facture liée
print '<td class="liste_titre center">';
print $form->selectarray('search_linked', $linkedOptions, $search_linked, 0, 0, 0, '', 1, 0, 0, '', 'maxwidth100');
print '</td>';
// Actions filter buttons
if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

// Fields title label
print '<tr class="liste_titre">';
// Checkbox column
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList($form->showCheckAddButtons('checkforselect', 1), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}
print getTitleFieldOfList($langs->trans("Ref"), 0, $_SERVER['PHP_SELF'], 'er.ref', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("User"), 0, $_SERVER['PHP_SELF'], 'u.lastname', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Line"), 0, $_SERVER['PHP_SELF'], 'd.rowid', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Date"), 0, $_SERVER['PHP_SELF'], 'd.date', '', $param, 'class="center"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Project"), 0, $_SERVER['PHP_SELF'], 'p.ref', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Type"), 0, $_SERVER['PHP_SELF'], 'tf.label', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Description"), 0, $_SERVER['PHP_SELF'], 'd.comments', '', $param, '', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("VATRate"), 0, $_SERVER['PHP_SELF'], 'd.tva_tx', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("PriceUHT"), 0, $_SERVER['PHP_SELF'], 'd.subprice', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("Qty"), 0, $_SERVER['PHP_SELF'], 'd.qty', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("AmountHT"), 0, $_SERVER['PHP_SELF'], 'd.total_ht', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("AmountTTC"), 0, $_SERVER['PHP_SELF'], 'd.total_ttc', '', $param, 'class="right"', $sortfield, $sortorder)."\n";
print getTitleFieldOfList($langs->trans("LinkedInvoice"), 0, $_SERVER['PHP_SELF'], 'f.ref', '', $param, 'class="center"', $sortfield, $sortorder)."\n";
// Checkbox column (right side)
if (!$conf->main_checkbox_left_column) {
	print getTitleFieldOfList($form->showCheckAddButtons('checkforselect', 1), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}
print '</tr>'."\n";

// Loop on records
$userstatic = new User($db);
$i = 0;
$arrayofselected = is_array($toselect) ? $toselect : array();
$imaxinloop = ($limit ? min($num, $limit) : $num);
$totalarray = array('total_ht' => 0, 'total_ttc' => 0);

while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break;
	}

	$selected = 0;
	if (in_array($obj->rowid, $arrayofselected)) {
		$selected = 1;
	}

	$is_linked = ($obj->fk_facture > 0 && !empty($obj->facture_id));

	print '<tr class="oddeven">';

	// Checkbox (left)
	if ($conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if (!$is_linked) {
			// Only allow selection of unlinked lines
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		} else {
			print '<span class="opacitymedium">—</span>';
		}
		print '</td>';
	}

	// Reference (expense report)
	print '<td class="nowrap">';
	$expensereport = new ExpenseReport($db);
	$expensereport->id = $obj->fk_expensereport;
	$expensereport->ref = $obj->expensereport_ref;
	print $expensereport->getNomUrl(1);
	print '</td>';

	// User / Owner
	print '<td class="nowrap">';
	if ($obj->user_id > 0) {
		$userstatic->id = $obj->user_id;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->login = $obj->login;
		$userstatic->gender = $obj->gender;
		$userstatic->photo = $obj->photo;
		$userstatic->statut = $obj->user_statut;
		$userstatic->entity = $obj->user_entity;
		print $userstatic->getNomUrl(1);
	}
	print '</td>';

	// Line ID
	print '<td>'.$obj->rowid.'</td>';

	// Date
	print '<td class="center">'.dol_print_date($db->jdate($obj->date_line), 'day').'</td>';

	// Project
	print '<td class="nowrap">';
	if ($obj->fk_projet > 0 && isModEnabled('project')) {
		$project = new Project($db);
		$project->id = $obj->fk_projet;
		$project->ref = $obj->project_ref;
		$project->title = $obj->project_title;
		print $project->getNomUrl(1);
	}
	print '</td>';

	// Type
	$type_label = $obj->type_code ? $langs->trans($obj->type_code) : '';
	if ($type_label == $obj->type_code || empty($type_label)) {
		$type_label = $obj->type_label ? $langs->trans($obj->type_label) : '';
	}
	print '<td>'.dol_escape_htmltag($type_label).'</td>';

	// Description
	print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->comments).'">'.dol_htmlentitiesbr(dol_trunc($obj->comments, 80)).'</td>';

	// TVA
	print '<td class="right">'.vatrate($obj->tva_tx, true).'</td>';

	// P.U. HT
	print '<td class="right nowrap">'.price($obj->subprice).'</td>';

	// Qty
	print '<td class="right">'.price($obj->qty).'</td>';

	// Total HT
	print '<td class="right nowrap">'.price($obj->total_ht).'</td>';
	$totalarray['total_ht'] += $obj->total_ht;

	// Total TTC
	print '<td class="right nowrap">'.price($obj->total_ttc).'</td>';
	$totalarray['total_ttc'] += $obj->total_ttc;

	// Facture liée
	print '<td class="center nowrap">';
	if ($is_linked && $obj->facture_ref) {
		$facturestatic = new Facture($db);
		$facturestatic->id = $obj->facture_id;
		$facturestatic->ref = $obj->facture_ref;
		print $facturestatic->getNomUrl(1);
		print ' <span class="amount">(' . price($obj->facturedet_ttc) . ' TTC)</span>';
		if ($obj->facture_statut == Facture::STATUS_DRAFT) {
			print ' <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=ask_unlinkline&expdet_id='.$obj->rowid.'" title="'.$langs->trans('Unlink').'">';
			print img_picto($langs->trans('Unlink'), 'unlink', 'class="paddingleft"');
			print '</a>';
		} else {
			print ' <span title="'.dol_escape_htmltag($langs->trans('FraisproInvoiceClosedCannotUnlink')).'">';
			print img_picto($langs->trans('FraisproInvoiceClosedCannotUnlink'), 'lock', 'class="paddingleft"');
			print '</span>';
		}
	} else {
		print '<span class="opacitymedium">'.$langs->trans('NotLinked').'</span>';
		print ' <a class="fraispro-link-open" href="#" data-expdet-id="'.$obj->rowid.'" title="'.$langs->trans('LinkToInvoiceLine').'">';
		print img_picto($langs->trans('LinkToInvoiceLine'), 'link', 'class="paddingleft"');
		print '</a>';
	}
	print '</td>';

	// Checkbox (right)
	if (!$conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if (!$is_linked) {
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		} else {
			print '<span class="opacitymedium">—</span>';
		}
		print '</td>';
	}

	print '</tr>'."\n";
	$i++;
}

// Totals row
if ($num > 0) {
	print '<tr class="liste_total">';
	if ($conf->main_checkbox_left_column) {
		print '<td></td>';
	}
	print '<td colspan="10" class="right">'.$langs->trans("Total").'</td>';
	print '<td class="right nowrap">'.price($totalarray['total_ht']).'</td>';
	print '<td class="right nowrap">'.price($totalarray['total_ttc']).'</td>';
	print '<td></td>';
	if (!$conf->main_checkbox_left_column) {
		print '<td></td>';
	}
	print '</tr>';
}

$db->free($resql);

print '</table>';
print '</div>';
print '</form>';

// Form to submit link action
print '<form id="fraispro-link-form" method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="linkline">';
print '<input type="hidden" name="expdet_id" id="fraispro-link-form-expdet" value="">';
print '<input type="hidden" name="facturedet_id" id="fraispro-link-form-facturedet" value="">';
print '</form>';

// Dialog HTML
print '<div id="fraispro-link-dialog" style="display: none;">';
print '  <input type="hidden" id="fraispro-link-expdet-id" value="">';
print '  <div class="div-table-responsive-no-max">';
print '    <table class="noborder centpercent">';
print '      <tr>';
print '        <td class="titlefield">'.dol_escape_htmltag($langs->trans("TargetInvoice")).'</td>';
print '        <td>';
print '          <select id="fraispro-invoice-status" class="flat valignmiddle maxwidth150 marginright">';
print '            <option value="0">'.dol_escape_htmltag($langs->trans("Draft")).'</option>';
print '            <option value="-1">'.dol_escape_htmltag($langs->trans("All")).'</option>';
print '          </select>';
print '          <select id="fraispro-invoice-select" class="flat valignmiddle minwidth200">';
print '            <option value="">'.dol_escape_htmltag($langs->trans("SelectInvoice")).'</option>';
print '          </select>';
print '        </td>';
print '      </tr>';
print '      <tr>';
print '        <td>'.dol_escape_htmltag($langs->trans("LinkToInvoiceLine")).'</td>';
print '        <td>';
print '          <select id="fraispro-invoiceline-select" class="flat centpercent">';
print '            <option value="">'.dol_escape_htmltag($langs->trans("SelectInvoiceLine")).'</option>';
print '          </select>';
print '        </td>';
print '      </tr>';
print '    </table>';
print '  </div>';
print '  <div class="margin-top-10 center">';
print '    <button id="fraispro-confirm-link" class="button">'.dol_escape_htmltag($langs->trans("Link")).'</button>';
print '  </div>';
print '</div>';

// Script block for JS configurations
print '<script type="text/javascript">';
print '  window.fraispro = window.fraispro || {};';
print '  window.fraispro.link = window.fraispro.link || {};';
print '  window.fraispro.link.selectInvoiceLabel = "'.dol_escape_js($langs->transnoentitiesnoconv("SelectInvoice")).'";';
print '  window.fraispro.link.selectLineLabel = "'.dol_escape_js($langs->transnoentitiesnoconv("SelectInvoiceLine")).'";';
print '  window.fraispro.link.modalTitle = "'.dol_escape_js($langs->transnoentitiesnoconv("LinkToInvoiceLine")).'";';
print '  window.fraispro.link.unlinkConfirmLabel = "'.dol_escape_js($langs->transnoentitiesnoconv("ConfirmUnlink")).'";';
print '  window.fraispro.link.ajaxUrl = "'.dol_escape_js(DOL_URL_ROOT . '/custom/fraispro/ajax/fraispro_invoice.php').'";';
print '  window.fraispro.link.token = "'.dol_escape_js(newToken()).'";';
print '</script>';

llxFooter();
$db->close();

