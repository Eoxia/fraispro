<?php
/**
 * \file    core/tpl/frontend/fraispro_pwa_bottom_nav.tpl.php
 * \ingroup fraispro
 * \brief   Bottom navigation bar for mobile/App frontend pages
 */
global $langs;
$landingPage = getDolUserString('MAIN_LANDING_PAGE', getDolGlobalString('MAIN_LANDING_PAGE'));
$dolibarrUrl = !empty($landingPage) ? dol_buildpath($landingPage, 1) : DOL_URL_ROOT . '/index.php';
?>

<style>
.fraispro-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 65px;
    background-color: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 1000;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
}

.fraispro-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    font-size: 11px;
    font-weight: 500;
    flex: 1;
    height: 100%;
    transition: color 0.2s;
}

.fraispro-nav-item i {
    font-size: 20px;
    margin-bottom: 4px;
}

.fraispro-nav-item:hover, .fraispro-nav-item:active, .fraispro-nav-item.active {
    color: #3b82f6;
}
</style>

<nav class="fraispro-bottom-nav">
    <a href="<?php echo dol_buildpath('/custom/fraispro/view/frontend/app.php', 1); ?>" class="fraispro-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'app.php' ? 'active' : ''); ?>">
        <i class="fas fa-camera"></i>
        <span>Scan Reçu</span>
    </a>
    <a href="<?php echo dol_buildpath('/custom/fraispro/view/frontend/traitement.php', 1); ?>" class="fraispro-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'traitement.php' ? 'active' : ''); ?>">
        <i class="fas fa-list"></i>
        <span>Traitement</span>
    </a>
    <a href="<?php echo dol_escape_htmltag($dolibarrUrl); ?>" class="fraispro-nav-item">
        <i class="fas fa-sign-out-alt"></i>
        <span>Quitter</span>
    </a>
</nav>
