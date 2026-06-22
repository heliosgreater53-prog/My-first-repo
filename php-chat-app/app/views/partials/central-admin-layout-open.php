<?php
$activeAdminPage = $activeAdminPage ?? 'dashboard';
$adminPageTitles = [
    'dashboard' => 'Dashboard',
    'users' => 'Users',
    'rooms' => 'Rooms',
    'audit' => 'Audit logs',
    'settings' => 'Settings',
];
$adminTopbarTitle = $adminPageTitles[$activeAdminPage] ?? 'Central Admin';
?>
<main class="admin-app">
    <div class="admin-shell" id="adminShell">
        <?php require view_path('partials.central-admin-sidebar'); ?>
        <div class="admin-workspace">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-button" id="adminMenuBtn" aria-label="Open admin menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="admin-topbar-title">
                    <strong>LivingSpring Central Admin</strong>
                    <span><?= htmlspecialchars($adminTopbarTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <a class="admin-text-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>" title="Back to app"><i class="bi bi-box-arrow-up-right"></i></a>
            </header>
            <section class="admin-main">
