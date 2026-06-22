<?php
$activeAdminPage = $activeAdminPage ?? 'dashboard';
$adminPageTitles = [
    'dashboard' => 'Dashboard',
    'users' => 'Users',
    'chats' => 'Chat review',
    'rooms' => 'Rooms',
    'audit' => 'Audit logs',
];
$adminTopbarTitle = $adminPageTitles[$activeAdminPage] ?? 'Admin';
?>
<main class="admin-app">
    <div class="admin-shell" id="adminShell">
        <?php require view_path('partials.admin-sidebar'); ?>
        <div class="admin-workspace">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-button" id="adminMenuBtn" aria-label="Open admin menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="admin-topbar-title">
                    <strong>LivingSpring Admin</strong>
                    <span><?= htmlspecialchars($adminTopbarTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <a class="admin-text-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>" title="Back to app"><i class="bi bi-box-arrow-up-right"></i></a>
            </header>
            <section class="admin-main">
