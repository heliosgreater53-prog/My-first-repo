<?php
$activeAdminPage = $activeAdminPage ?? 'dashboard';
$adminNav = [
    'dashboard' => ['label' => 'Home', 'icon' => 'bi-speedometer2', 'url' => url('/admin/dashboard')],
    'users' => ['label' => is_admin() ? 'Users' : 'Class', 'icon' => 'bi-people', 'url' => url('/admin/users')],
    'chats' => ['label' => 'Chats', 'icon' => 'bi-chat-text', 'url' => url('/admin/chats')],
    'rooms' => ['label' => 'Rooms', 'icon' => 'bi-door-open', 'url' => url('/admin/rooms')],
    'audit' => ['label' => 'Logs', 'icon' => 'bi-journal-text', 'url' => url('/admin/audit-logs')],
];
if (!is_admin()) {
    unset($adminNav['dashboard'], $adminNav['rooms'], $adminNav['audit']);
}
?>
<nav class="admin-mobile-nav" aria-label="Admin navigation">
    <?php foreach ($adminNav as $key => $item): ?>
        <a class="admin-mobile-nav-item<?= $activeAdminPage === $key ? ' is-active' : ''; ?>" href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>
</nav>
