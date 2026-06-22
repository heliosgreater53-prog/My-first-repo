<?php
$activeAdminPage = $activeAdminPage ?? 'dashboard';
$adminLinks = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => url('/admin/dashboard'), 'admin_only' => true],
    'users' => ['label' => is_admin() ? 'Users' : 'Class users', 'icon' => 'bi-people', 'url' => url('/admin/users'), 'admin_only' => false],
    'chats' => ['label' => 'Chat review', 'icon' => 'bi-chat-square-text', 'url' => url('/admin/chats'), 'admin_only' => false],
    'rooms' => ['label' => 'Rooms', 'icon' => 'bi-door-open', 'url' => url('/admin/rooms'), 'admin_only' => true],
    'audit' => ['label' => 'Audit logs', 'icon' => 'bi-journal-text', 'url' => url('/admin/audit-logs'), 'admin_only' => true],
    'settings' => ['label' => 'Settings', 'icon' => 'bi-gear', 'url' => url('/admin/settings'), 'admin_only' => true],
];
?>
<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="admin-sidebar-brand">
        <div class="rail-mark" aria-hidden="true">LS</div>
        <div>
            <strong>LivingSpring</strong>
            <span>Administration</span>
        </div>
    </div>

    <nav class="admin-sidebar-nav" aria-label="Admin sections">
        <p class="admin-sidebar-label">Menu</p>
        <?php foreach ($adminLinks as $key => $link): ?>
            <?php if (!empty($link['admin_only']) && !is_admin()) { continue; } ?>
            <a class="admin-sidebar-link<?= $activeAdminPage === $key ? ' is-active' : ''; ?>" href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                <span><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-actions">
        <a class="admin-sidebar-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-arrow-left"></i>
            <span>Back to app</span>
        </a>
        <?php if (is_admin()): ?>
            <a class="admin-sidebar-link" href="<?= htmlspecialchars(url('/admin'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-shield-lock"></i>
                <span>Re-verify access</span>
            </a>
        <?php endif; ?>
    </div>
</aside>
