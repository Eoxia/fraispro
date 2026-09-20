<?php
/**
 * \file    core/tpl/frontend/fraispro_pwa_header.tpl.php
 * \ingroup fraispro
 * \brief   Homogeneous top header for all App pages
 */
?>
<div id="id-top" class="page-header-tabs" style="position: fixed; top: 0; left: 0; right: 0; z-index: 999; width: 100%; box-sizing: border-box; margin: 0; border-radius: 0; background-color: #ffffff; padding: 0 15px; height: 60px; border-bottom: 2px solid #3b82f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between;">
    
    <!-- Left: Logo -->
    <a href="<?php echo dol_buildpath('/custom/fraispro/view/frontend/app.php', 1); ?>" class="company-logo-wrapper" style="display: flex; align-items: center; text-decoration: none;">
        <?php
        global $mysoc, $db, $conf, $user;
        if (empty($mysoc)) {
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            $mysoc = new Societe($db);
            $mysoc->setMysoc($conf);
        }
        $logoFile = '';
        if (!empty($mysoc->logo_squarred)) {
            $logoFile = 'logos/'.$mysoc->logo_squarred;
        } elseif (!empty($mysoc->logo)) {
            $logoFile = 'logos/'.$mysoc->logo;
        }
        if (!empty($logoFile)) {
            $logoUrl = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode($logoFile);
            print '<img class="company-logo" src="'.$logoUrl.'" alt="Logo" style="max-height: 40px; max-width: 140px; object-fit: contain;">';
        } else {
            print '<span style="font-weight:bold; color:#3b82f6;">Frais.pro</span>';
        }
        ?>
    </a>
    
    <!-- Center: Page Specific Indicators -->
    <div class="pwa-header-center" style="display: flex; align-items: center; justify-content: center; flex: 1; margin: 0 15px;">
        <?php 
        if (!empty($pwaHeaderCenterHtml)) {
            print $pwaHeaderCenterHtml;
        }
        ?>
    </div>

    <!-- Right: User Profile Badge -->
    <a class="user-profile-widget reedcrm-hover-bg" href="<?php echo dol_escape_htmltag(dol_buildpath('/user/card.php', 1) . '?id=' . $user->id); ?>" style="display: flex; align-items: center; gap: 12px; cursor: pointer; color: #1e293b; text-decoration: none; padding: 4px 10px; border-radius: 8px; transition: background 0.2s; border: 1px solid transparent;">
        <?php
        $formObj = new Form($db);
        $nativeAvatar = $formObj->showphoto('userphoto', $user, 0, 0, 0, 'custom-badge-avatar', 'small', 0);
        
        print '<div class="user-avatar-wrap" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; background: transparent;">';
        print $nativeAvatar;
        print '</div>';
        ?>
    </a>
</div>

<style>
    /* Global App layout alignment */
    body.template-pwa {
        padding: 0 !important;
        padding-top: 60px !important; /* Offset content exactly by the height of the fixed navbar */
        margin: 0 !important;
    }
    
    body.template-pwa .fiche,
    body.template-pwa .fichecenter,
    body.template-pwa .page-content {
        margin: 0 !important;
        padding: 15px !important;
        padding-bottom: 90px !important; /* Offset for bottom nav */
    }

    /* Force the native Dolibarr Form::showphoto element to fit symmetrically inside the 32x32px boundary */
    .custom-badge-avatar {
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        object-fit: contain !important;
        border: none !important;
    }
</style>
