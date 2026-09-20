<?php
/* Copyright (C) 2026  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/custom/fraispro/core/modules/fraispro/mod_fraisproreceipt_advanced.php
 * \ingroup    fraispro
 * \brief      File that contains the numbering module rules Advanced
 */

require_once DOL_DOCUMENT_ROOT.'/custom/fraispro/core/modules/fraispro/modules_fraispro.php';

/**
 * Class of file that contains the numbering module rules Advanced
 */
class mod_fraisproreceipt_advanced extends ModeleNumRefFraispro
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int		Position
	 */
	public $position = 40;

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Advanced';

	/**
	 * @var string model name
	 */
	public $name = 'Advanced';

	/**
	 *  Return description of module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $conf, $langs, $db;

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconst" value="FRAISPRO_FRAISPRORECEIPT_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("FraisproReceipt"), $langs->transnoentities("FraisproReceipt"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("FraisproReceipt"), $langs->transnoentities("FraisproReceipt"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$mask = !getDolGlobalString('FRAISPRO_FRAISPRORECEIPT_MASK') ? '' : $conf->global->FRAISPRO_FRAISPRORECEIPT_MASK;
		$texte .= '<td class="right nowraponall">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskvalue" value="'.$mask.'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';

		$texte .= '<td class="left"><input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Save").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT . '/custom/fraispro/class/fraispro_receipt.class.php';

		$obj = new FraisproReceipt($db);
		$obj->initAsSpecimen();
		$numExample = $this->getNextValue($obj);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}

		return $numExample;
	}

	/**
	 *  Return next value
	 *
	 *  @param	CommonObject $object      Object we need next value for
	 *  @return string|int<-1,0>		Next value, <=0 if KO
	 */
	public function getNextValue($object)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// On defini critere recherche compteur
		$mask = !getDolGlobalString('FRAISPRO_FRAISPRORECEIPT_MASK') ? '' : $conf->global->FRAISPRO_FRAISPRORECEIPT_MASK;

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		// Get entities
		$entity = getEntity('fraispro_receipt', 1, $object);

		$date = empty($object->date_creation) ? dol_now() : $object->date_creation;

		$numFinal = get_next_value($db, $mask, 'fraispro_receipt', 'ref', '', null, $date, 'next', false, null, $entity);

		return  $numFinal;
	}
}
