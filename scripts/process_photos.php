<?php
/**
 * Script de traitement des photos de notes de frais (rotation automatique EXIF)
 */

$res = 0;
if (!($inc = @include '../../main.inc.php')) {
	if (!($inc = @include '../../../main.inc.php')) {
		if (!($inc = @include '../../../../main.inc.php')) {
			if (!($inc = @include '../../../../../main.inc.php')) {
				die("main.inc.php not found");
			}
		}
	}
}

// Check access
// This script is accessible from web interface (so we check user rights)
if (empty($user->rights->fraispro->read)) {
	accessforbidden();
}

$langs->load("main");
// $langs->load("fraispro@fraispro"); // Assuming the lang file exists

$action = GETPOST('action', 'aZ09');
$file = GETPOST('file', 'alpha');

$dir_test = dol_buildpath('/fraispro/test/datasets', 0);
if (!is_dir($dir_test)) {
	dol_mkdir($dir_test);
}

/**
 * Corrige l'orientation de l'image en se basant sur les données EXIF.
 * 
 * @param string $filepath Chemin absolu du fichier
 * @return bool Vrai si l'image a été tournée, faux sinon
 */
function fraispro_autorotate_image($filepath)
{
	if (!function_exists('exif_read_data')) {
		return false;
	}

	$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
	if (!in_array($ext, array('jpg', 'jpeg'))) {
		return false;
	}

	$exif = @exif_read_data($filepath);
	if (empty($exif) || !isset($exif['Orientation'])) {
		return false;
	}

	$orientation = $exif['Orientation'];
	if (empty($orientation) || $orientation == 1) {
		return false; // Déjà bien orientée ou orientation inconnue
	}

	$image = @imagecreatefromjpeg($filepath);
	if (!$image) {
		return false;
	}

	$rotated = false;
	switch ($orientation) {
		case 3:
			$image = imagerotate($image, 180, 0);
			$rotated = true;
			break;
		case 6:
			$image = imagerotate($image, -90, 0);
			$rotated = true;
			break;
		case 8:
			$image = imagerotate($image, 90, 0);
			$rotated = true;
			break;
	}

	if ($rotated) {
		// Sauvegarde l'image et supprime l'originale en mémoire
		imagejpeg($image, $filepath, 95);
	}
	
	imagedestroy($image);
	
	return $rotated;
}

// --- ACTIONS ---

if ($action == 'process' && !empty($file)) {
	$filename = dol_basename($file);
	$filepath = $dir_test . '/' . $filename;
	
	if (file_exists($filepath) && preg_match('/\.(jpg|jpeg|png)$/i', $filename)) {
		$rotated = fraispro_autorotate_image($filepath);
		
		if ($rotated) {
			setEventMessages("L'image ".$filename." a été ré-orientée avec succès.", null, 'mesgs');
		} else {
			setEventMessages("L'image ".$filename." n'avait pas besoin d'être tournée ou manque de données EXIF.", null, 'warnings');
		}
	} else {
		setEventMessages("Fichier invalide ou introuvable.", null, 'errors');
	}
	
	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'process_all') {
	$files = scandir($dir_test);
	$count = 0;
	
	foreach ($files as $filename) {
		if ($filename == '.' || $filename == '..') continue;
		
		$filepath = $dir_test . '/' . $filename;
		if (is_file($filepath) && preg_match('/\.(jpg|jpeg)$/i', $filename)) {
			if (fraispro_autorotate_image($filepath)) {
				$count++;
			}
		}
	}
	
	setEventMessages($count . " image(s) ré-orientée(s) avec succès.", null, 'mesgs');
	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}

// --- VUES ---

llxHeader('', 'Traitement des photos');

print load_fiche_titre('Traitement automatique des photos (Orientation EXIF)');

print '<div class="info">Ce script analyse le dossier <strong>' . htmlspecialchars($dir_test) . '</strong> et tourne automatiquement les images JPG si leur balise EXIF d\'orientation le demande.</div>';

print '<div class="tabsAction">';
print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=process_all">Tout traiter automatiquement</a>';
print '</div>';

$files = scandir($dir_test);
$has_files = false;

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Nom du fichier</td>';
print '<td>Aperçu</td>';
print '<td>Action</td>';
print '</tr>';

foreach ($files as $filename) {
	if ($filename == '.' || $filename == '..') continue;
	
	$filepath = $dir_test . '/' . $filename;
	if (is_file($filepath) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
		$has_files = true;
		
		// L'URL de l'image (attention, le dossier test n'est pas forcément accessible publiquement via htdocs, 
		// mais s'il est dans custom/fraispro/test/datasets, on peut construire l'URL)
		$img_url = dol_buildpath('/fraispro/test/datasets/'.$filename, 1);
		
		print '<tr class="oddeven">';
		print '<td>' . htmlspecialchars($filename) . '</td>';
		print '<td><img src="' . htmlspecialchars($img_url) . '" style="max-height: 100px; max-width: 100px;" alt="Aperçu"></td>';
		print '<td><a class="button" href="'.$_SERVER["PHP_SELF"].'?action=process&file='.urlencode($filename).'">Vérifier et tourner</a></td>';
		print '</tr>';
	}
}

if (!$has_files) {
	print '<tr><td colspan="3"><span class="opacitymedium">Aucune image trouvée dans le dossier de test.</span></td></tr>';
}

print '</table>';

llxFooter();
$db->close();
