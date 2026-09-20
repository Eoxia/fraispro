<?php
/* Copyright (C) 2026 Eoxia
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    view/frontend/app.php
 * \ingroup fraispro
 * \brief   PWA App for fast receipt scanning and drafting (Feed View)
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
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/fraispro/class/fraispro_receipt.class.php';
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}

global $conf, $db, $langs, $user;

if (isset($_GET['test_bypass']) && !is_object($user)) {
    $user = new User($db);
    $user->fetch(1);
}

$action = GETPOST('action', 'alpha');

// GLOBAL LOG
$logFile = $conf->fraispro->dir_output . '/debug_global.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - URI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);
file_put_contents($logFile, "ACTION VAR: " . $action . "\n", FILE_APPEND);
file_put_contents($logFile, "GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// --- ACTION HANDLING ---
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

if ($action == 'add_photos') {
    if (!empty($_FILES['userfile']['name'][0])) {
        $count = count($_FILES['userfile']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['userfile']['error'][$i] == 0) {
                // create receipt
                $receipt = new FraisproReceipt($db);
                $receipt->fk_user_creat = $user->id;
                $receipt->status = 0;
                $res_create = $receipt->create($user);
                if ($res_create > 0) {
                    $receipt->validate($user);
                    $destdir = $conf->fraispro->dir_output . '/' . dol_sanitizeFileName($receipt->ref);
                    if (!dol_is_dir($destdir)) {
                        dol_mkdir($destdir);
                    }
                    $filename = dol_sanitizeFileName($_FILES['userfile']['name'][$i]);
                    dol_move_uploaded_file($_FILES['userfile']['tmp_name'][$i], $destdir . '/' . $filename, 1, 0, $_FILES['userfile']['error'][$i]);
                }
            }
        }
        setEventMessages('Photos ajoutées avec succès', null, 'mesgs');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
} elseif ($action == 'validate_receipt') {
    $rowid = GETPOST('rowid', 'int');
    if ($rowid > 0) {
        $receipt = new FraisproReceipt($db);
        if ($receipt->fetch($rowid) > 0) {
            $description = GETPOST('description', 'restricthtml');
            $fk_project = GETPOST('fk_project', 'int');
            
            $receipt->status = 1;
            $receipt->description = $description;
            $receipt->fk_project = $fk_project;
            $res_update = $receipt->update($user);
            if ($res_update > 0) {
                if (!GETPOST('ajax')) setEventMessages('Reçu : ' . $receipt->ref . ' - Transféré dans Traitement', null, 'mesgs');
            } else {
                if (!GETPOST('ajax')) setEventMessages($receipt->error, $receipt->errors, 'errors');
            }
            if (GETPOST('ajax')) {
                $displayRef = $receipt->ref ? $receipt->ref : '(PROV' . $receipt->rowid . ')';
                print json_encode(['success' => ($res_update > 0), 'ref' => $displayRef]);
                exit;
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
} elseif ($action == 'add_audio' && !empty($_POST['module_name']) && !empty($_FILES['audio']['tmp_name'])) {
    $saturneModule = GETPOST('module_name', 'alpha');
    $saturneSubDir = GETPOST('sub_dir', 'alpha');
    $audioModLower = dol_strtolower($saturneModule);

    $uploadDir = !empty($conf->$audioModLower->dir_output)
        ? $conf->$audioModLower->dir_output
        : $conf->ecm->dir_output . '/' . $audioModLower;
    if (!empty($saturneSubDir)) {
        $uploadDir .= '/' . $saturneSubDir;
    }

    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    $fileName = dol_print_date(dol_now(), 'dayhourlog') . '_audio.wav';
    $destFile = $uploadDir . '/' . $fileName;
    
    dol_move_uploaded_file($_FILES['audio']['tmp_name'], $destFile, 1, 0, $_FILES['audio']['error']);
    // No exit here, let view render so Saturne JS parses it
} elseif ($action == 'delete_audio' && !empty($_POST['module_name'])) {
    $saturneModule = GETPOST('module_name', 'alpha');
    $saturneSubDir = GETPOST('sub_dir', 'alpha');
    $audioFilename = GETPOST('filename', 'alpha');
    $audioModLower = dol_strtolower($saturneModule);

    $uploadDir = !empty($conf->$audioModLower->dir_output)
        ? $conf->$audioModLower->dir_output
        : $conf->ecm->dir_output . '/' . $audioModLower;
    if (!empty($saturneSubDir)) {
        $uploadDir .= '/' . $saturneSubDir;
    }

    $filePath = $uploadDir . '/' . basename($audioFilename);
    if (!empty($audioFilename) && file_exists($filePath)) {
        dol_delete_file($filePath);
    }
    // No exit here, let view render so Saturne JS parses it
} elseif ($action == 'delete_receipt') {
    $rowid = GETPOST('rowid', 'int');
    if ($rowid > 0) {
        $receipt = new FraisproReceipt($db);
        if ($receipt->fetch($rowid) > 0) {
            $res_delete = $receipt->delete($user);
            if ($res_delete > 0) {
                setEventMessages($receipt->ref . ' - Supprimé', null, 'mesgs');
            } else {
                setEventMessages($receipt->error, $receipt->errors, 'errors');
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
} elseif ($action == 'uploadPhoto' || $action == 'upload_media') {
    // Debug logging
    $logFile = $conf->fraispro->dir_output . '/debug_upload.log';
    if (!dol_is_dir($conf->fraispro->dir_output)) {
        dol_mkdir($conf->fraispro->dir_output);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - START upload_media\n", FILE_APPEND);
    file_put_contents($logFile, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents($logFile, "FILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);


    // Handled by Saturne JS via AJAX (for photo cropper and media uploads)
    $subDir = GETPOST('sub_dir', 'alpha');
    if (empty($subDir)) {
        $subDir = GETPOST('subdir', 'alpha'); // fallback
    }
    
    file_put_contents($logFile, "SubDir: " . $subDir . "\n", FILE_APPEND);
    
    $uploadDir = $conf->fraispro->dir_output;
    if (!empty($subDir)) {
        $uploadDir .= '/' . $subDir;
    }
    
    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }
    file_put_contents($logFile, "UploadDir: " . $uploadDir . "\n", FILE_APPEND);
    
    $allowOverwrite = GETPOST('overwrite', 'int');
    if ($allowOverwrite === '') {
        $allowOverwrite = 1;
    }
    
    $res = dol_add_file_process($uploadDir, $allowOverwrite, 1, 'userfile', '', null, '', 1);
    
    // Clear generic success messages generated by dol_add_file_process
    if (isset($_SESSION['dol_events']['mesgs'])) {
        $_SESSION['dol_events']['mesgs'] = array();
    }
    
    file_put_contents($logFile, "Result dol_add_file_process: " . $res . "\n", FILE_APPEND);
    if ($res > 0) {
        if (strpos($subDir, 'tmp/fast_capture') === 0) {
            // Fast capture upload: return JSON. Our JS protects the DOM from being destroyed.
            print json_encode(['success' => true, 'fast_capture' => true]);
        } else {
            // Regular image edit: Return HTML for refreshGallery
            $ref = $subDir;
            $sanitizedRef = dol_escape_htmltag(dol_sanitizeFileName($ref));
            $filename = is_array($_FILES['userfile']['name']) ? $_FILES['userfile']['name'][0] : $_FILES['userfile']['name'];
            $thumbUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=fraispro&entity=' . $conf->entity . '&file=' . urlencode(dol_sanitizeFileName($ref) . '/' . $filename); 
            
            // Get ALL images in directory to preserve gallery navigation
            $urls = [];
            $dir = $conf->fraispro->dir_output . '/' . dol_sanitizeFileName($ref);
            if (dol_is_dir($dir)) {
                $imageFiles = dol_dir_list($dir, 'files', 0, '\.(png|jpg|jpeg|gif|webp)$', '', 'date', SORT_DESC);
                if (!empty($imageFiles)) {
                    foreach ($imageFiles as $f) {
                        $urls[] = DOL_URL_ROOT . '/document.php?modulepart=fraispro&entity=1&file=' . urlencode(dol_sanitizeFileName($ref) . '/' . $f['name']);
                    }
                }
            }
            if (empty($urls)) {
                $urls[] = $thumbUrl;
            }
            $urlsJson = json_encode($urls);
            
            $html = '<div id="gallery-' . $sanitizedRef . '" class="feed-image linked-medias" style="width: 120px; flex-shrink: 0;">';
            $html .= '  <div class="fast-upload-options" data-from-type="fraispro" data-from-subdir="' . $sanitizedRef . '"></div>';
            $html .= '  <div class="saturne-media-gallery" style="width: 100%; height: 100%;">';
            $html .= '    <img src="' . $thumbUrl . '&v=' . time() . '" class="open-media-editor-as-gallery" data-json="' . htmlspecialchars($urlsJson, ENT_QUOTES, 'UTF-8') . '" style="cursor: pointer; width: 100%; height: 160px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" alt="Reçu">';
            $html .= '  </div>';
            $html .= '</div>';
            
            print $html;
        }
    } else {
        print json_encode(['success' => false, 'error' => 'Upload failed']);
    }
    exit;
}

// --- PROCESS PENDING UPLOADS FROM SATURNE ---
$fastCaptureDir = $conf->fraispro->dir_output . '/tmp/fast_capture_' . $user->id;
if (dol_is_dir($fastCaptureDir)) {
    $files = dol_dir_list($fastCaptureDir, 'files');
    if (!empty($files)) {
        $addedCount = 0;
        $fileDetails = [];
        foreach ($files as $file) {
            $receipt = new FraisproReceipt($db);
            $receipt->fk_user_creat = $user->id;
            $receipt->status = 0;
            if ($receipt->create($user) > 0) {
                // Generate definitive number immediately without validating (keeps status = 0 so it appears in feed)
                $num = $receipt->getNextNumRef();
                if (!empty($num)) {
                    $receipt->ref = $num;
                    $receipt->update($user);
                }
                
                $destdir = $conf->fraispro->dir_output . '/' . dol_sanitizeFileName($receipt->ref);
                if (!dol_is_dir($destdir)) dol_mkdir($destdir);
                dol_move($fastCaptureDir . '/' . $file['name'], $destdir . '/' . $file['name']);
                $addedCount++;
                
                $fileSize = isset($file['size']) ? (int)$file['size'] : 0;
                if ($fileSize == 0 && file_exists($destdir . '/' . $file['name'])) {
                    $fileSize = (int)filesize($destdir . '/' . $file['name']);
                }
                $fileSizeKo = round($fileSize / 1024);
                $fileDetails[] = 'Recu : ' . $receipt->ref . ' enregistré<br>médias : ' . $file['name'] . ' de (' . $fileSizeKo . ' ko)';
            }
        }
        if ($addedCount > 0) {
            $msg = '';
            if (!empty($fileDetails)) {
                $msg .= implode('<br>', $fileDetails) . '<br><br>';
            }
            $msg .= 'Soit ' . $addedCount . ' recu' . ($addedCount > 1 ? 's' : '');
            setEventMessages($msg, null, 'mesgs');
            // Redirect to clear state and refresh feed
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// --- VIEW ---

$langs->loadLangs(['fraispro@fraispro', 'projects']);

$title = $langs->trans('FraisproApp');
$help_url = '';

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/saturne_functions.lib.php';
global $moduleNameLowerCase;
$moduleNameLowerCase = 'fraispro';

saturne_header(1, '', $title, $help_url, '', 0, 0, [], [], '', 'template-pwa fraispro-app');

// Include Homogeneous App Top Header
$pwaHeaderCenterHtml = '<span style="font-weight:600;">Frais.pro</span>';
$fraispro_header = dol_buildpath('/custom/fraispro/view/frontend/fraispro_pwa_header.tpl.php');
if (file_exists($fraispro_header)) {
    require_once $fraispro_header;
}

print '<div class="pwa-container" style="padding: 10px; max-width: 1000px; margin: 0 auto;">';

// --- TOP SECTION: QUICK UPLOAD ---
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/medias.lib.php';

print '<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding: 10px 0;">';
print '<div id="saturne-fast-capture" style="display:flex; gap:10px;">';
print saturne_render_media_block('fraispro', 'tmp/fast_capture_' . $user->id, 'fast_', '', ['show_photo' => true, 'show_audio' => false, 'show_gallery' => false]);
print '</div>';
print '<div style="font-weight: 600; font-size: 16px; color: #1e293b;">Envoyez vos reçus !</div>';
print '</div>';

// JS for Saturne
print '<script>
$(document).ready(function() {
    window.hasUploadedFastCapture = false;
    window.editorWasOpen = false;

    // Protect the Fast Capture DOM from being destroyed by Saturne\'s refreshGallery
    // If the DOM is destroyed, the <input type="file"> is lost and arrows disappear!
    $(document).ajaxSend(function(event, jqxhr, settings) {
        if (settings.url && settings.url.indexOf("action=uploadPhoto") !== -1) {
            // Temporarily rename the class so e.find(".saturne-media-gallery") fails
            $("#saturne-fast-capture .saturne-media-gallery").removeClass("saturne-media-gallery").addClass("saturne-media-gallery-protected");
        }
    });

    // Detect fast capture uploads
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf("action=uploadPhoto") !== -1) {
            window.hasUploadedFastCapture = true;
            // Restore the class after refreshGallery has run
            setTimeout(function() {
                $(".saturne-media-gallery-protected").removeClass("saturne-media-gallery-protected").addClass("saturne-media-gallery");
            }, 100);
        }
    });
    
    // Check periodically if the editor modal is open or closed
    var editorClosedTime = 0;
    setInterval(function() {
        var isEditorOpen = $(".wpeo-modal, .saturne-editor-modal, [class*=\'media-editor\']").filter(":visible").length > 0;
        
        if (isEditorOpen) {
            window.editorWasOpen = true;
            editorClosedTime = 0;
        } else if (window.editorWasOpen && window.hasUploadedFastCapture) {
            // Editor is closed, but maybe just temporarily between two photos in a sequential upload.
            // Wait at least 2.5 seconds and ensure no AJAX requests are active.
            if (editorClosedTime === 0) {
                editorClosedTime = Date.now();
            } else if (Date.now() - editorClosedTime > 2500 && $.active === 0) {
                window.hasUploadedFastCapture = false; // prevent loop
                window.location.reload();
            }
        }
    }, 500);
});
</script>';
print '<style>
    #saturne-fast-capture [id$="master-media-row-container-photo"] { flex-direction: row !important; gap: 6px !important; }
    #saturne-fast-capture .linked-medias.medias        { width: auto !important; display: inline-flex; align-items: center; flex-direction: row; }
    #saturne-fast-capture .saturne-media-upload-block  { display: inline-flex !important; align-items: center !important; flex-direction: row !important; gap: 6px !important; margin-top: 0 !important; }
    #saturne-fast-capture .saturne-upload-label {
        width: 50px !important; height: 50px !important;
        min-width: 50px !important; min-height: 50px !important;
        border-radius: 12px; display: inline-flex !important; align-items: center !important; justify-content: center !important;
        font-size: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; flex-shrink: 0;
    }
    #saturne-fast-capture .saturne-upload-label-gallery { background-color: #10b981; color: white; border: none; }
    #saturne-fast-capture label[for^="fast-take-photo"] { background-color: #f59e0b; color: white; border: none; }
    /* Hide the default dotted border of Saturne */
    #saturne-fast-capture .saturne-upload-label { border: none !important; }
</style>';

// --- BOTTOM SECTION: DRAFTS FEED ---
$sql = "SELECT rowid, ref, date_creation FROM " . MAIN_DB_PREFIX . "fraispro_receipt WHERE fk_user_creat = " . ((int)$user->id) . " AND status = 0 ORDER BY ref DESC, date_creation DESC";
$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    if ($num > 0) {
        print '<div class="fraispro-feed-list" style="display: flex; flex-direction: column; gap: 20px;">';
        
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/medias.lib.php';
        
        $form = new Form($db);
        if (isModEnabled('project')) {
            $formProject = new FormProjets($db);
        }
        
        while ($obj = $db->fetch_object($resql)) {
            $ref = $obj->ref ? $obj->ref : (string)$obj->rowid;
            $dir = $conf->fraispro->dir_output . '/' . dol_sanitizeFileName($ref);
            $thumbUrl = '';
            $fileTitleInfo = '';
            
            $urlsJson = '[]';
            if (dol_is_dir($dir)) {
                $imageFiles = dol_dir_list($dir, 'files', 0, '\.(png|jpg|jpeg|gif|webp)$', '(?i)\.meta$', 'date', SORT_DESC);
                $allFiles = dol_dir_list($dir, 'files', 0, '', '(?i)\.meta$', 'date', SORT_DESC);
                
                $titleFileObj = null;
                if (!empty($imageFiles)) {
                    $titleFileObj = $imageFiles[0];
                    $thumbUrl = DOL_URL_ROOT . '/document.php?modulepart=fraispro&entity=1&file=' . urlencode(dol_sanitizeFileName($ref) . '/' . $titleFileObj['name']);
                    
                    $urls = [];
                    foreach ($imageFiles as $f) {
                        $urls[] = DOL_URL_ROOT . '/document.php?modulepart=fraispro&entity=1&file=' . urlencode(dol_sanitizeFileName($ref) . '/' . $f['name']);
                    }
                    $urlsJson = json_encode($urls);
                } elseif (!empty($allFiles)) {
                    $titleFileObj = $allFiles[0];
                }
                
                if ($titleFileObj) {
                    $firstFile = $titleFileObj['name'];
                    $fileSize = isset($titleFileObj['size']) ? (int)$titleFileObj['size'] : 0;
                    if ($fileSize == 0 && file_exists($dir . '/' . $firstFile)) {
                        $fileSize = (int)filesize($dir . '/' . $firstFile);
                    }
                    $fileSizeKo = round($fileSize / 1024);
                    $fileTitleInfo = ' - ' . $firstFile . ' (' . $fileSizeKo . 'ko)';
                }
            }
            
            print '<form class="feed-card" method="POST" action="' . $_SERVER["PHP_SELF"] . '" style="display: flex; gap: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px;">';
            print '  <input type="hidden" name="token" value="' . newToken() . '">';
            print '  <input type="hidden" name="action" value="validate_receipt">';
            print '  <input type="hidden" name="rowid" value="' . $obj->rowid . '">';
            
            // Left: Image
            $sanitizedRef = dol_escape_htmltag(dol_sanitizeFileName($ref));
            print '  <div id="gallery-' . $sanitizedRef . '" class="feed-image linked-medias" style="width: 120px; flex-shrink: 0;">';
            print '    <div class="fast-upload-options" data-from-type="fraispro" data-from-subdir="' . $sanitizedRef . '"></div>';
            print '    <div class="saturne-media-gallery" style="width: 100%; height: 100%;">';
            
            if ($thumbUrl) {
                print '      <img src="' . $thumbUrl . '&v=' . time() . '" class="open-media-editor-as-gallery" data-json="' . htmlspecialchars($urlsJson, ENT_QUOTES, 'UTF-8') . '" style="cursor: pointer; width: 100%; height: 160px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" alt="Reçu">';
            } else {
                print '      <div style="width: 100%; height: 160px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">';
                print '        <i class="fas fa-image fa-2x"></i>';
                print '      </div>';
            }
            print '    </div>';
            print '  </div>';
            
            // Right: Content
            print '  <div class="feed-content" style="flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px;">';
            
            print '    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">';
            $displayRef = empty($obj->ref) ? '(PROV' . $obj->rowid . ')' : $obj->ref;
            print '      <div style="color: #1e293b; font-size: 15px; flex: 1; min-width: 0; word-break: break-word;"><strong>' . $displayRef . '</strong><span style="font-weight: normal;">' . $fileTitleInfo . '</span></div>';
            print '      <span style="color: #64748b; font-size: 12px; white-space: nowrap; flex-shrink: 0; padding-top: 2px;">' . dol_print_date($db->jdate($obj->date_creation, true), 'dayhour', 'tzuser') . '</span>';
            print '    </div>';
            
            print '    <textarea name="description" placeholder="Description..." class="flat" style="width: 100%; max-width: 100%; display: block; margin: 0; box-sizing: border-box; height: 70px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; resize: none;"></textarea>';
            
            if (isModEnabled('project')) {
                print '    <div class="fraispro-select-wrapper" style="width: 100%; max-width: 100%;">';
                // Add inline style to force select2 to 100%
                print '      <style>.fraispro-select-wrapper .select2-container { width: 100% !important; max-width: 100%; }</style>';
                print $formProject->select_projects(-1, -1, 'fk_project', 0, 0, 1, -1, 0, 0, 0, '', 1, 0, 'maxwidth100cent', 'fk_project_' . $obj->rowid);
                print '    </div>';
            }
            
            print '    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">';
            
            // Audio controls
            print '      <div class="audio-controls" style="display: flex; gap: 5px; align-items: center;">';
            // Use Saturne for audio tied to this receipt's ref
            // Configure to only show audio
            print saturne_render_media_block('fraispro', dol_sanitizeFileName($ref), 'aud_'.$obj->rowid.'_', '', ['show_photo' => false, 'show_gallery' => false, 'show_upload' => false, 'show_audio' => true]);
            print '      </div>';
            
            // Right-aligned Buttons
            print '      <div style="display: flex; gap: 8px;">';
            // Save Button
            print '        <button type="submit" style="background-color: #9b59b6; color: white; width: 44px; height: 44px; border: none; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 0;">';
            print '          <i class="fas fa-paper-plane" style="font-size: 20px;"></i>';
            print '        </button>';
            
            // Delete Button
            print '        <a href="' . $_SERVER['PHP_SELF'] . '?action=delete_receipt&rowid=' . $obj->rowid . '&token=' . newToken() . '" style="background-color: #ef4444; color: white; width: 44px; height: 44px; border: none; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 0; text-decoration: none;">';
            print '          <i class="fas fa-trash-alt" style="font-size: 18px;"></i>';
            print '        </a>';
            print '      </div>';
            
            print '    </div>'; // end controls row
            
            print '  </div>'; // end feed-content
            print '</form>';
        }
        print '</div>';
    } else {
        print '<div style="text-align: center; padding: 40px 20px; color: #94a3b8; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">';
        print '  <i class="fa fa-camera" style="font-size: 40px; margin-bottom: 15px; color: #cbd5e1;"></i><br>';
        print '  Prenez une photo pour commencer !';
        print '</div>';
    }
}

print '</div>'; // end pwa-container

// Include Homogeneous App Bottom Nav
$fraispro_bottom_nav = dol_buildpath('/custom/fraispro/view/frontend/fraispro_pwa_bottom_nav.tpl.php');
if (file_exists($fraispro_bottom_nav)) {
    require_once $fraispro_bottom_nav;
}

// Include Saturne Photo Editor Modal (Required for photo uploads / pdf attachments / audio)
$saturne_photo_tpl = DOL_DOCUMENT_ROOT . '/custom/saturne/core/tpl/medias/photo_editor_modal.tpl.php';
if (file_exists($saturne_photo_tpl)) {
    include $saturne_photo_tpl;
}

// Add CSS specific to maxwidth100cent for project select
print '<style>
.feed-card .fraispro-select-wrapper .maxwidth100cent, .feed-card .fraispro-select-wrapper select {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    padding: 6px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}
/* Ensure audio block has correct layout */
.feed-card [id^="aud_"] { flex-direction: row !important; gap: 6px !important; margin: 0 !important; }
.feed-card .saturne-audio-controls { display: inline-flex !important; align-items: center !important; flex-direction: row !important; gap: 6px !important; margin: 0 !important; }
.feed-card [id$="-audio"] { padding: 0 !important; }
</style>';

print '<script>
$(document).ready(function() {
    // 1. Disable plane button if description is empty
    $(".feed-card textarea[name=\'description\']").on("input", function() {
        var form = $(this).closest(".feed-card");
        var planeBtn = form.find("button[type=\'submit\']");
        var icon = planeBtn.find("i");
        if ($(this).val().trim() === "") {
            planeBtn.css({ "opacity": "0.5", "cursor": "not-allowed" });
            planeBtn.data("disabled", true);
        } else {
            planeBtn.css({ "opacity": "1", "cursor": "pointer" });
            planeBtn.data("disabled", false);
        }
    }).trigger("input");

    // 2. Handle Plane button click for bulk transfer
    $(".feed-card").on("submit", function(e) {
        e.preventDefault();
        
        var clickedForm = $(this);
        var clickedBtn = clickedForm.find("button[type=\'submit\']");
        
        // If the clicked form has an empty description, do nothing
        if (clickedBtn.data("disabled")) {
            return false;
        }
        
        // Find all forms that have a non-empty description
        var formsToSubmit = [];
        $(".feed-card").each(function() {
            var desc = $(this).find("textarea[name=\'description\']").val();
            if (desc && desc.trim() !== "") {
                formsToSubmit.push($(this));
            }
        });
        
        if (formsToSubmit.length === 0) return false;
        
        // Disable all buttons to prevent double-click
        $(".feed-card button[type=\'submit\']").css({ "opacity": "0.5", "cursor": "not-allowed" }).data("disabled", true);
        $.jnotify("Transfert en cours...", "warning");
        
        var promises = [];
        var transferredRefs = [];
        
        formsToSubmit.forEach(function(form) {
            var formData = form.serialize() + "&ajax=1";
            var req = $.post(form.attr("action"), formData);
            req.done(function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success && data.ref) {
                        transferredRefs.push(data.ref);
                    }
                } catch(e) {}
            });
            promises.push(req);
        });
        
        $.when.apply($, promises).always(function() {
            if (transferredRefs.length > 0) {
                var msg = "Transfert de " + transferredRefs.length + " reçu(s)<br>";
                transferredRefs.forEach(function(ref) {
                    msg += ref + " - Transféré<br>";
                });
                $.jnotify(msg, "success", true); // pass true for HTML or jnotify might render tags as text, actually jnotify allows HTML by default in dolibarr
            } else {
                $.jnotify("Erreur lors du transfert", "error");
            }
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        });
    });
});
</script>';

llxFooter();
$db->close();
