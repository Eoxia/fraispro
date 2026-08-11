<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/../../class/fraispro_receipt.class.php';

$receipt = new FraisproReceipt($db);
$res = $receipt->fetch($id);
if ($res <= 0) {
    print 'Reçu introuvable';
    exit;
}

$dir = $conf->fraispro->dir_output . '/' . dol_sanitizeFileName($receipt->ref);
$urls = [];
$thumbUrl = '';
if (dol_is_dir($dir)) {
    $files = dol_dir_list($dir, 'files', 0, '\.(png|jpg|jpeg|gif|webp)$', '', 'date', SORT_DESC);
    if (!empty($files)) {
        foreach ($files as $file) {
            $urls[] = DOL_URL_ROOT . '/document.php?modulepart=fraispro&entity=1&file=' . urlencode(dol_sanitizeFileName($receipt->ref) . '/' . $file['name']);
        }
        $thumbUrl = $urls[0];
    }
}
$urlsJson = json_encode($urls);

print '<div class="pwa-container" style="padding: 10px; max-width: 1400px; margin: 0 auto;">';

// Header / Back button
print '<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">';
print '  <h2><a href="?action=" style="color: #64748b; margin-right: 15px;"><i class="fa fa-arrow-left"></i></a> Traitement du reçu ' . $receipt->ref . '</h2>';
print '</div>';

print '<div class="traitement-split-view" style="display: flex; gap: 20px; flex-wrap: wrap;">';

// LEFT: Image Viewer
print '<div class="traitement-left" style="flex: 1; min-width: 300px; background: #1e293b; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 500px;">';
if ($thumbUrl) {
    print '  <img src="' . $thumbUrl . '&v='.time().'" class="open-media-editor-as-gallery" data-json="' . htmlspecialchars($urlsJson, ENT_QUOTES, 'UTF-8') . '" style="max-width: 100%; max-height: 80vh; object-fit: contain; cursor: pointer; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);" title="Cliquez pour modifier (Saturne)" alt="Reçu">';
    print '  <div style="color: #94a3b8; font-size: 13px; margin-top: 15px;"><i class="fa fa-info-circle"></i> Cliquez sur l\'image pour l\'éditer</div>';
} else {
    print '  <div style="color: #64748b; text-align: center;"><i class="fa fa-image fa-3x"></i><br>Aucune image attachée</div>';
}
print '</div>';

// RIGHT: Form
print '<div class="traitement-right" style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';

print '<form id="form-traitement" method="POST" action="?action=save_traitement">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="id" value="' . $receipt->rowid . '">';

$langs->load('trips');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
$form = new Form($db);

// Form Header
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">';
print '  <div><label>Référence</label><input type="text" id="field-receipt-ref" name="ref" value="' . $receipt->ref . '" class="flat" style="width:100%"></div>';
print '  <div><label>Date</label><input type="date" id="field-receipt-date" name="date_receipt" value="' . $receipt->date_receipt . '" class="flat" style="width:100%"></div>';
print '</div>';

print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">';
print '  <div class="fraispro-type-select"><label>Type de frais</label><br>';
$form->select_type_fees($receipt->fk_c_type_fees, 'fk_c_type_fees', 1);
print '  </div>';
print '  <div><label>Projet</label><br>';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
$formproject = new FormProjets($db);
print $formproject->select_projects(-1, $receipt->fk_project, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'style="width:100%"');
print '  </div>';
print '</div>';
print '<style>.fraispro-type-select select { width: 100% !important; }</style>';

print '<div style="margin-bottom: 20px;">';
print '  <label>Description</label>';
print '  <textarea id="field-receipt-description" name="description" class="flat" style="width: 100%; height: 60px;">' . dol_htmlentities($receipt->description) . '</textarea>';
print '</div>';

// Form Totals
print '<div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0;">';
print '  <h4 style="margin-top:0; margin-bottom: 10px; color: #334155;">Montants Totaux (TTC)</h4>';
print '  <div style="display: flex; gap: 15px;">';
print '    <div style="flex: 1;"><label>Total HT</label><input type="number" step="0.01" id="field-receipt-total-ht" name="total_ht" value="' . ($receipt->total_ht ? number_format($receipt->total_ht, 2, '.', '') : '0.00') . '" class="flat total-input" style="width:100%; font-weight: bold;"></div>';
print '    <div style="flex: 1;"><label>Total TVA</label><input type="number" step="0.01" id="field-receipt-total-tva" name="total_tva" value="' . ($receipt->total_tva ? number_format($receipt->total_tva, 2, '.', '') : '0.00') . '" class="flat total-input" style="width:100%; font-weight: bold;"></div>';
print '    <div style="flex: 1;"><label>Total TTC</label><input type="number" step="0.01" id="field-receipt-total-ttc" name="total_ttc" value="' . ($receipt->total_ttc ? number_format($receipt->total_ttc, 2, '.', '') : '0.00') . '" class="flat total-input" style="width:100%; font-weight: bold;"></div>';
print '  </div>';
print '</div>';

// Restant à ventiler
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 10px 15px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; color: #166534;">';
print '  <strong>Restant à saisir</strong>';
print '  <div style="display: flex; gap: 15px;">';
print '    <span>HT: <span id="restant-ht">0.00</span></span>';
print '    <span>TVA: <span id="restant-tva">0.00</span></span>';
print '    <span>TTC: <span id="restant-ttc">0.00</span></span>';
print '  </div>';
print '</div>';

// Lignes
print '<div style="margin-bottom: 20px;">';
print '  <table class="noborder centpercent" id="table-lines">';
print '    <tr class="liste_titre">';
print '      <th>TVA %</th>';
print '      <th class="right">P.U. HT</th>';
print '      <th class="right">P.U. TTC</th>';
print '      <th class="right">Qté</th>';
print '      <th class="right">Montant HT</th>';
print '      <th class="right">Montant TTC</th>';
print '      <th width="30"></th>';
print '    </tr>';
// Fetch existing lines
$sqlLines = "SELECT rowid, fk_c_type_fees, description, tva_tx, pu_ht, pu_ttc, qty, total_ht, total_ttc FROM " . MAIN_DB_PREFIX . "fraispro_receipt_det WHERE fk_fraispro_receipt = " . (int)$receipt->rowid;
$resLines = $db->query($sqlLines);
if ($resLines) {
    while ($line = $db->fetch_object($resLines)) {
        print '    <tr class="active-line" style="background: #fff;">';
        print '      <td>';
        print '        <select name="lines_tva[]" class="flat line-tva" style="width:70px;">';
        print '          <option value="20" ' . ($line->tva_tx == 20 ? 'selected' : '') . '>20%</option>';
        print '          <option value="10" ' . ($line->tva_tx == 10 ? 'selected' : '') . '>10%</option>';
        print '          <option value="5.5" ' . ($line->tva_tx == 5.5 ? 'selected' : '') . '>5.5%</option>';
        print '          <option value="2.1" ' . ($line->tva_tx == 2.1 ? 'selected' : '') . '>2.1%</option>';
        print '          <option value="0" ' . ($line->tva_tx == 0 ? 'selected' : '') . '>0%</option>';
        print '        </select>';
        print '      </td>';
        print '      <td class="right"><input type="number" step="0.01" name="lines_pu_ht[]" value="' . ($line->pu_ht ? number_format($line->pu_ht, 2, '.', '') : '0.00') . '" class="flat line-pu-ht right" style="width:80px;"></td>';
        print '      <td class="right"><input type="number" step="0.01" name="lines_pu_ttc[]" value="' . ($line->pu_ttc ? number_format($line->pu_ttc, 2, '.', '') : '0.00') . '" class="flat line-pu-ttc right" style="width:80px;"></td>';
        print '      <td class="right"><input type="number" step="1" name="lines_qty[]" value="' . ($line->qty ? (int)$line->qty : 1) . '" class="flat line-qty right" style="width:60px;"></td>';
        print '      <td class="right"><input type="number" step="0.01" name="lines_ht[]" value="' . ($line->total_ht ? number_format($line->total_ht, 2, '.', '') : '0.00') . '" class="flat line-ht right" style="width:80px;" readonly></td>';
        print '      <td class="right"><input type="number" step="0.01" name="lines_ttc[]" value="' . ($line->total_ttc ? number_format($line->total_ttc, 2, '.', '') : '0.00') . '" class="flat line-ttc right" style="width:80px;" readonly></td>';
        print '      <td class="center"><a href="#" class="btn-delete-line" style="color:#ef4444;"><i class="fa fa-trash"></i></a></td>';
        print '    </tr>';
    }
    $db->free($resLines);
}

print '    <tr id="line-template" style="display:none; background: #fff;">';
print '      <td>';
print '        <select name="lines_tva[]" class="flat line-tva" style="width:70px;">';
print '          <option value="20">20%</option>';
print '          <option value="10">10%</option>';
print '          <option value="5.5">5.5%</option>';
print '          <option value="2.1">2.1%</option>';
print '          <option value="0">0%</option>';
print '        </select>';
print '      </td>';
print '      <td class="right"><input type="number" step="0.01" name="lines_pu_ht[]" class="flat line-pu-ht right" style="width:80px;"></td>';
print '      <td class="right"><input type="number" step="0.01" name="lines_pu_ttc[]" class="flat line-pu-ttc right" style="width:80px;"></td>';
print '      <td class="right"><input type="number" step="1" name="lines_qty[]" value="1" class="flat line-qty right" style="width:60px;"></td>';
print '      <td class="right"><input type="number" step="0.01" name="lines_ht[]" class="flat line-ht right" style="width:80px;" readonly></td>';
print '      <td class="right"><input type="number" step="0.01" name="lines_ttc[]" class="flat line-ttc right" style="width:80px;" readonly></td>';
print '      <td class="center"><a href="#" class="btn-delete-line" style="color:#ef4444;"><i class="fa fa-trash"></i></a></td>';
print '    </tr>';
print '  </table>';
print '  <button type="button" id="btn-add-line" class="button" style="margin-top:10px; background: #6366f1; color: white; border: none; padding: 6px 12px;"><i class="fa fa-plus"></i> AJOUTER</button>';
print '</div>';

// Destination
print '<div style="border-top: 1px solid #e2e8f0; padding-top: 20px; margin-bottom: 20px;">';
print '  <label><strong>Envoyer vers :</strong></label>';
print '  <div style="display:flex; gap: 20px; margin-top: 10px;">';
print '    <label><input type="radio" name="destination" value="ndf" checked> Note de frais</label>';
print '    <label><input type="radio" name="destination" value="fourn"> Facture fournisseur</label>';
print '  </div>';
print '</div>';

print '<div style="text-align: right;">';
print '  <button type="submit" id="btn-save-draft" class="button" style="margin-right: 10px;"><i class="fa fa-save"></i> Enregistrer Brouillon</button>';
print '  <button type="submit" class="button" style="background: #8b5cf6; color: white; border-color: #7c3aed; padding: 10px 20px; font-weight: bold;"><i class="fa fa-paper-plane"></i> VALIDER ET ENVOYER</button>';
print '</div>';

print '</form>';
print '</div>'; // end right column

print '</div>'; // end split view

// JS Logic for calculation
print '<script>';
print '$(document).ready(function() {';
print '  function calculateRestant() {';
print '    let totalHT = parseFloat($("#field-receipt-total-ht").val()) || 0;';
print '    let totalTVA = parseFloat($("#field-receipt-total-tva").val()) || 0;';
print '    let totalTTC = parseFloat($("#field-receipt-total-ttc").val()) || 0;';
print '    let sumLinesHT = 0, sumLinesTVA = 0, sumLinesTTC = 0;';
print '    $("#table-lines tr.active-line").each(function() {';
print '      let tva = parseFloat($(this).find(".line-tva").val()) || 0;';
print '      let puHT = parseFloat($(this).find(".line-pu-ht").val()) || 0;';
print '      let puTTC = parseFloat($(this).find(".line-pu-ttc").val()) || 0;';
print '      let qty = parseFloat($(this).find(".line-qty").val()) || 1;';
print '      let lHT = puHT * qty;';
print '      let lTTC = puTTC * qty;';
print '      let lTVA = lTTC - lHT;';
print '      $(this).find(".line-ht").val(lHT.toFixed(2));';
print '      $(this).find(".line-ttc").val(lTTC.toFixed(2));';
print '      sumLinesHT += lHT;';
print '      sumLinesTTC += lTTC;';
print '      sumLinesTVA += lTVA;';
print '    });';
print '    let rHT = totalHT - sumLinesHT;';
print '    let rTVA = totalTVA - sumLinesTVA;';
print '    let rTTC = totalTTC - sumLinesTTC;';
print '    $("#restant-ht").text(rHT.toFixed(2));';
print '    $("#restant-tva").text(rTVA.toFixed(2));';
print '    $("#restant-ttc").text(rTTC.toFixed(2));';
print '  }';
print '  $("#btn-add-line").click(function(e) {';
print '    e.preventDefault();';
print '    let clone = $("#line-template").clone();';
print '    clone.removeAttr("id").addClass("active-line").show();';
print '    $("#table-lines").append(clone);';
print '    calculateRestant();';
print '  });';
print '  $(document).on("click", ".btn-delete-line", function(e) {';
print '    e.preventDefault();';
print '    $(this).closest("tr").remove();';
print '    calculateRestant();';
print '  });';
print '  $(document).on("input", ".total-input, .line-pu-ht, .line-qty", function() {';
print '    let $row = $(this).closest("tr");';
print '    if ($row.hasClass("active-line")) {';
print '       let puHT = parseFloat($row.find(".line-pu-ht").val()) || 0;';
print '       let tva = parseFloat($row.find(".line-tva").val()) || 0;';
print '       let puTTC = puHT * (1 + tva / 100);';
print '       $row.find(".line-pu-ttc").val(puTTC.toFixed(2));';
print '    }';
print '    calculateRestant();';
print '  });';
print '  $(document).on("input", ".line-pu-ttc", function() {';
print '    let $row = $(this).closest("tr");';
print '    if ($row.hasClass("active-line")) {';
print '       let puTTC = parseFloat($(this).val()) || 0;';
print '       let tva = parseFloat($row.find(".line-tva").val()) || 0;';
print '       let puHT = puTTC / (1 + tva / 100);';
print '       $row.find(".line-pu-ht").val(puHT.toFixed(2));';
print '    }';
print '    calculateRestant();';
print '  });';
print '  $(document).on("change", ".line-tva", function() {';
print '    let $row = $(this).closest("tr");';
print '    if ($row.hasClass("active-line")) {';
print '       let puHT = parseFloat($row.find(".line-pu-ht").val()) || 0;';
print '       let tva = parseFloat($(this).val()) || 0;';
print '       let puTTC = puHT * (1 + tva / 100);';
print '       $row.find(".line-pu-ttc").val(puTTC.toFixed(2));';
print '    }';
print '    calculateRestant();';
print '  });';
print '  calculateRestant();';

print '  let lastChangedTotaux = [];';
print '  $("#field-receipt-total-ht, #field-receipt-total-tva, #field-receipt-total-ttc").on("input", function() {';
print '     let id = $(this).attr("id");';
print '     if (lastChangedTotaux[0] !== id) {';
print '         lastChangedTotaux.unshift(id);';
print '         if (lastChangedTotaux.length > 2) lastChangedTotaux.pop();';
print '     }';
print '     let ht = parseFloat($("#field-receipt-total-ht").val()) || 0;';
print '     let tva = parseFloat($("#field-receipt-total-tva").val()) || 0;';
print '     let ttc = parseFloat($("#field-receipt-total-ttc").val()) || 0;';
print '     if (lastChangedTotaux.includes("field-receipt-total-ht") && lastChangedTotaux.includes("field-receipt-total-tva")) {';
print '         ttc = ht + tva;';
print '         $("#field-receipt-total-ttc").val(ttc.toFixed(2));';
print '     } else if (lastChangedTotaux.includes("field-receipt-total-ht") && lastChangedTotaux.includes("field-receipt-total-ttc")) {';
print '         tva = ttc - ht;';
print '         $("#field-receipt-total-tva").val(tva.toFixed(2));';
print '     } else if (lastChangedTotaux.includes("field-receipt-total-tva") && lastChangedTotaux.includes("field-receipt-total-ttc")) {';
print '         ht = ttc - tva;';
print '         $("#field-receipt-total-ht").val(ht.toFixed(2));';
print '     }';
print '     calculateRestant();';
print '  });';

// Autosave logic
print '  let saveTimer;';
print '  let changedInputs = [];';
print '  function autoSave() {';
print '    clearTimeout(saveTimer);';
print '    saveTimer = setTimeout(function() {';
print '      let data = $("#form-traitement").serialize() + "&ajax=1";';
print '      let inputsToAnimate = changedInputs;';
print '      changedInputs = [];';
print '      $.post("?action=save_traitement", data, function(response) {';
print '        if(response.success) {';
print '          inputsToAnimate.forEach(function($input) {';
print '            $input.css({"border-color": "#22c55e", "box-shadow": "0 0 0 2px rgba(34, 197, 94, 0.2)", "transition": "all 0.3s ease"});';
print '            setTimeout(function() { $input.css({"border-color": "", "box-shadow": ""}); }, 1500);';
print '          });';
print '        }';
print '      });';
print '    }, 1000);';
print '  }';
print '  $("#form-traitement").on("input change", "input, select, textarea", function(e) {';
print '    let $target = $(this);';
print '    let alreadyTracking = false;';
print '    changedInputs.forEach(function($el) { if($el.is($target)) alreadyTracking = true; });';
print '    if (!alreadyTracking) { changedInputs.push($target); }';
print '    autoSave();';
print '  });';

print '});';
print '</script>';

print '</div>'; // end pwa-container
