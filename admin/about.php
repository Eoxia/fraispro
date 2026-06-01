<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Replace with your own copyright and developer email---
 * Copyright (C) 2024       FrÃ©dÃ©ric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/admin/about.php
 * \ingroup fraispro
 * \brief   About page of module Fraispro.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
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
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/fraispro.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("errors", "admin", "fraispro@fraispro"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "FraisproSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-fraispro page-admin_about');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = fraisproAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($title), 0, 'fraispro@fraispro');

dol_include_once('/fraispro/core/modules/modFraispro.class.php');
$tmpmodule = new modFraispro($db);

// Custom About view
print '<div class="about-hero" style="background: linear-gradient(135deg, #63ACC9 0%, #357CA5 100%); color: #fff; padding: 30px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
print '  <h1 style="margin: 0 0 10px 0; font-size: 28px; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">' . img_picto('', 'fraispro@fraispro', 'class="pictofixedwidth valignmiddle marginright" style="filter: brightness(0) invert(1); width: 32px; height: 32px;"') . ' Module Fraispro</h1>';
print '  <p style="margin: 0; font-size: 16px; opacity: 0.9; line-height: 1.6;">';
print '    Optimisez la gestion et la refacturation de vos notes de frais dans Dolibarr. Fraispro simplifie la liaison de vos lignes de frais avec vos factures clients, permettant un suivi précis de vos marges et une automatisation de la refacturation.';
print '  </p>';
print '</div>';

print '<div class="fichecenter">';
print '  <div class="fichehalfleft">';

// Features Card
print '    <div class="card" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">';
print '      <h3 style="margin-top: 0; color: #357CA5; border-bottom: 2px solid #63ACC9; padding-bottom: 8px; font-size: 18px;">' . img_picto('', 'bullet', 'class="paddingright"') . ' Fonctionnalités Clés</h3>';
print '      <ul style="list-style-type: none; padding-left: 0; line-height: 1.8;">';
print '        <li style="margin-bottom: 12px;">';
print '          <strong>' . img_picto('', 'link', 'class="paddingright"') . ' Liaison 1:1 Avancée</strong><br>';
print '          Associez directement chaque ligne de frais (<span class="opacitymedium">llx_expensereport_det</span>) à sa ligne de facture correspondante (<span class="opacitymedium">llx_facturedet</span>) pour une traçabilité sans faille.';
print '        </li>';
print '        <li style="margin-bottom: 12px;">';
print '          <strong>' . img_picto('', 'add', 'class="paddingright"') . ' Refacturation en Masse</strong><br>';
print '          Sélectionnez plusieurs lignes de frais non liées et injectez-les en un clic dans une facture brouillon existante.';
print '        </li>';
print '        <li style="margin-bottom: 12px;">';
print '          <strong>' . img_picto('', 'money', 'class="paddingright"') . ' Gestion des Marges</strong><br>';
print '          Appliquez des marges sous forme de montants fixes ou de pourcentages lors de la refacturation automatique.';
print '        </li>';
print '        <li style="margin-bottom: 12px;">';
print '          <strong>' . img_picto('', 'stats', 'class="paddingright"') . ' Suivi et Reporting</strong><br>';
print '          Suivez en temps réel le statut de refacturation de vos lignes de frais (refacturé, en attente, montants totaux).';
print '        </li>';
print '      </ul>';
print '    </div>';

print '  </div>';
print '  <div class="fichehalfright">';

// Technical specifications / Info Card
print '    <div class="card" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">';
print '      <h3 style="margin-top: 0; color: #357CA5; border-bottom: 2px solid #63ACC9; padding-bottom: 8px; font-size: 18px;">' . img_picto('', 'info', 'class="paddingright"') . ' Informations Techniques</h3>';
print '      <table class="noborder centpercent" style="line-height: 2;">';
print '        <tr>';
print '          <td class="titlefield">Version du module</td>';
print '          <td><span class="badge badge-info" style="background-color: #63ACC9; color: #fff; padding: 3px 8px; border-radius: 4px; font-weight: bold;">' . $tmpmodule->version . '</span></td>';
print '        </tr>';
print '        <tr>
          <td>Auteur / Éditeur</td>
          <td><strong>Eoxia</strong> (<a href="https://github.com/Eoxia/fraispro" target="_blank">github.com/Eoxia/fraispro</a>)</td>
        </tr>
        <tr>
          <td>Site de l\'extension</td>
          <td><a href="https://frais.pro/" target="_blank">frais.pro</a></td>
        </tr>
        <tr>
          <td>Compatibilité Dolibarr</td>
          <td>v23+</td>
        </tr>';
print '        <tr>';
print '          <td>Licence</td>';
print '          <td>GPLv3 ou supérieure</td>';
print '        </tr>';
print '      </table>';
print '    </div>';

print '  </div>';
print '</div>';


// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
