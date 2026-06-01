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
 * \file       class/fraispro_link.class.php
 * \ingroup    fraispro
 * \brief      Utility class to manage links between expense report lines and invoice lines
 */

/**
 * Class FraisproLink
 *
 * Manages the 1:1 relationship between expense report lines (llx_expensereport_det)
 * and invoice lines (llx_facturedet) using the native fk_facture column.
 */
class FraisproLink
{
	/**
	 * Link an expense report line to an invoice line
	 *
	 * @param  DoliDB  $db              Database handler
	 * @param  int     $expdet_id       Expense report line ID (llx_expensereport_det.rowid)
	 * @param  int     $facturedet_id   Invoice line ID (llx_facturedet.rowid)
	 * @return int                      1 if OK, <0 if KO
	 */
	public static function linkToInvoiceLine(DoliDB $db, int $expdet_id, int $facturedet_id): int
	{
		if ($expdet_id <= 0 || $facturedet_id <= 0) {
			return -1;
		}

		// Check that the invoice line exists
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".((int) $facturedet_id);
		$resql = $db->query($sql);
		if (!$resql || $db->num_rows($resql) == 0) {
			return -2;
		}

		// Check that the expense line exists
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."expensereport_det WHERE rowid = ".((int) $expdet_id);
		$resql = $db->query($sql);
		if (!$resql || $db->num_rows($resql) == 0) {
			return -3;
		}

		// Update the link
		$sql = "UPDATE ".MAIN_DB_PREFIX."expensereport_det";
		$sql .= " SET fk_facture = ".((int) $facturedet_id);
		$sql .= " WHERE rowid = ".((int) $expdet_id);

		$resql = $db->query($sql);
		if (!$resql) {
			return -4;
		}

		return 1;
	}

	/**
	 * Remove the link from an expense report line
	 *
	 * @param  DoliDB  $db          Database handler
	 * @param  int     $expdet_id   Expense report line ID
	 * @return int                  1 if OK, <0 if KO
	 */
	public static function unlinkFromInvoiceLine(DoliDB $db, int $expdet_id): int
	{
		if ($expdet_id <= 0) {
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."expensereport_det";
		$sql .= " SET fk_facture = 0";
		$sql .= " WHERE rowid = ".((int) $expdet_id);

		$resql = $db->query($sql);
		if (!$resql) {
			return -2;
		}

		return 1;
	}

	/**
	 * Get the invoice line linked to an expense report line
	 *
	 * @param  DoliDB  $db          Database handler
	 * @param  int     $expdet_id   Expense report line ID
	 * @return object|null          Object with invoice line data, or null
	 */
	public static function getLinkedInvoiceLine(DoliDB $db, int $expdet_id): ?object
	{
		if ($expdet_id <= 0) {
			return null;
		}

		$sql = "SELECT fd.rowid, fd.fk_facture, fd.description, fd.total_ht, fd.total_ttc, fd.qty,";
		$sql .= " f.ref as facture_ref, f.rowid as facture_id";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as d";
		$sql .= " JOIN ".MAIN_DB_PREFIX."facturedet as fd ON fd.rowid = d.fk_facture";
		$sql .= " JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
		$sql .= " WHERE d.rowid = ".((int) $expdet_id);
		$sql .= " AND d.fk_facture > 0";

		$resql = $db->query($sql);
		if ($resql && $db->num_rows($resql) > 0) {
			return $db->fetch_object($resql);
		}

		return null;
	}

	/**
	 * Get all expense report lines linked to a given invoice
	 *
	 * @param  DoliDB  $db          Database handler
	 * @param  int     $facture_id  Invoice ID (llx_facture.rowid)
	 * @return array                Array of objects with expense line data
	 */
	public static function getLinkedExpenseLines(DoliDB $db, int $facture_id): array
	{
		$result = array();

		if ($facture_id <= 0) {
			return $result;
		}

		$sql = "SELECT d.rowid, d.fk_expensereport, d.comments, d.total_ht, d.total_ttc, d.date,";
		$sql .= " er.ref as expensereport_ref,";
		$sql .= " fd.rowid as facturedet_id, fd.description as facturedet_desc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as d";
		$sql .= " JOIN ".MAIN_DB_PREFIX."expensereport as er ON er.rowid = d.fk_expensereport";
		$sql .= " JOIN ".MAIN_DB_PREFIX."facturedet as fd ON fd.rowid = d.fk_facture";
		$sql .= " WHERE fd.fk_facture = ".((int) $facture_id);
		$sql .= " AND d.fk_facture > 0";

		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$result[] = $obj;
			}
		}

		return $result;
	}

	/**
	 * Get statistics about linked/unlinked expense lines
	 *
	 * @param  DoliDB  $db  Database handler
	 * @return object       Object with total_lines, linked_lines, unlinked_lines, total_ht_linked, total_ht_unlinked
	 */
	public static function getStats(DoliDB $db): object
	{
		$stats = new \stdClass();
		$stats->total_lines = 0;
		$stats->linked_lines = 0;
		$stats->unlinked_lines = 0;
		$stats->total_ht_linked = 0;
		$stats->total_ht_unlinked = 0;

		$sql = "SELECT";
		$sql .= " COUNT(d.rowid) as total_lines,";
		$sql .= " SUM(CASE WHEN d.fk_facture > 0 THEN 1 ELSE 0 END) as linked_lines,";
		$sql .= " SUM(CASE WHEN d.fk_facture = 0 OR d.fk_facture IS NULL THEN 1 ELSE 0 END) as unlinked_lines,";
		$sql .= " SUM(CASE WHEN d.fk_facture > 0 THEN d.total_ht ELSE 0 END) as total_ht_linked,";
		$sql .= " SUM(CASE WHEN d.fk_facture = 0 OR d.fk_facture IS NULL THEN d.total_ht ELSE 0 END) as total_ht_unlinked";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as d";
		$sql .= " JOIN ".MAIN_DB_PREFIX."expensereport as er ON er.rowid = d.fk_expensereport";
		$sql .= " WHERE er.entity IN (".getEntity('expensereport').")";

		$resql = $db->query($sql);
		if ($resql && $db->num_rows($resql) > 0) {
			$obj = $db->fetch_object($resql);
			$stats->total_lines = (int) $obj->total_lines;
			$stats->linked_lines = (int) $obj->linked_lines;
			$stats->unlinked_lines = (int) $obj->unlinked_lines;
			$stats->total_ht_linked = (float) $obj->total_ht_linked;
			$stats->total_ht_unlinked = (float) $obj->total_ht_unlinked;
		}

		return $stats;
	}
}
