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
 * \file       ajax/fraispro_invoice.php
 * \ingroup    fraispro
 * \brief      AJAX endpoint to load invoices and invoice lines for linking
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Security check
if (!isModEnabled("fraispro")) {
	httponly_accessforbidden('Module fraispro not enabled');
}

$action = GETPOST('action', 'aZ09');

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

/*
 * Actions
 */

// Get list of draft invoices (status 0 = draft)
if ($action == 'getinvoices') {
	$search = GETPOST('q', 'alpha');
	$status = GETPOSTINT('status');
	if (empty($status) && $status !== 0) {
		$status = 0; // Default: draft invoices
	}

	$sql = "SELECT f.rowid, f.ref, f.ref_client, f.total_ht, f.total_ttc, f.fk_statut as status,";
	$sql .= " s.nom as thirdparty_name";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
	$sql .= " WHERE f.entity IN (".getEntity('facture').")";

	// Allow filtering by status (-1 = all)
	if ($status >= 0) {
		$sql .= " AND f.fk_statut = ".((int) $status);
	}

	if ($search) {
		$sql .= " AND (f.ref LIKE '%".$db->escape($search)."%'";
		$sql .= " OR s.nom LIKE '%".$db->escape($search)."%')";
	}

	$sql .= " ORDER BY f.ref DESC";
	$sql .= " LIMIT 50";

	$resql = $db->query($sql);
	$invoices = array();

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$invoices[] = array(
				'id' => (int) $obj->rowid,
				'ref' => $obj->ref,
				'ref_client' => $obj->ref_client,
				'thirdparty' => $obj->thirdparty_name,
				'total_ht' => (float) $obj->total_ht,
				'total_ttc' => (float) $obj->total_ttc,
				'status' => (int) $obj->status,
			);
		}
	}

	echo json_encode(array('success' => true, 'data' => $invoices));
	exit;
}

// Get lines of a specific invoice
if ($action == 'getinvoicelines') {
	$fk_facture = GETPOSTINT('fk_facture');

	if ($fk_facture <= 0) {
		echo json_encode(array('success' => false, 'error' => 'Invalid invoice ID'));
		exit;
	}

	$sql = "SELECT fd.rowid, fd.description, fd.qty, fd.subprice, fd.total_ht, fd.total_ttc, fd.tva_tx,";
	$sql .= " fd.product_type, fd.rang,";
	$sql .= " p.ref as product_ref, p.label as product_label";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = fd.fk_product";
	$sql .= " WHERE fd.fk_facture = ".((int) $fk_facture);
	$sql .= " ORDER BY fd.rang, fd.rowid";

	$resql = $db->query($sql);
	$lines = array();

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			// Build a display label
			$label = '';
			if ($obj->product_ref) {
				$label = '['.$obj->product_ref.'] '.$obj->product_label;
			}
			if ($obj->description) {
				$desc = strip_tags($obj->description);
				if (strlen($desc) > 80) {
					$desc = substr($desc, 0, 80).'...';
				}
				$label .= ($label ? ' - ' : '').$desc;
			}
			if (!$label) {
				$label = 'Ligne #'.$obj->rowid;
			}

			$lines[] = array(
				'id' => (int) $obj->rowid,
				'label' => $label,
				'description' => strip_tags($obj->description),
				'product_ref' => $obj->product_ref,
				'product_label' => $obj->product_label,
				'qty' => (float) $obj->qty,
				'subprice' => (float) $obj->subprice,
				'total_ht' => (float) $obj->total_ht,
				'total_ttc' => (float) $obj->total_ttc,
				'tva_tx' => (float) $obj->tva_tx,
			);
		}
	}

	echo json_encode(array('success' => true, 'data' => $lines));
	exit;
}

// Unknown action
echo json_encode(array('success' => false, 'error' => 'Unknown action'));
exit;
