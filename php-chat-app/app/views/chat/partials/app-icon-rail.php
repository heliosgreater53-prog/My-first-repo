<?php
$layoutMode = $layoutMode ?? 'feed';
$dmInboxCount = count(is_array($dmInboxRequests ?? null) ? $dmInboxRequests : []);
if ((int) ($user['dm_notifications_enabled'] ?? 1) !== 1 || (int) ($user['notifications_enabled'] ?? 1) !== 1) {
    $dmInboxCount = 0;
}
$notificationCount = (int) ($notificationCount ?? 0);
if ((int) ($user['notifications_enabled'] ?? 1) !== 1) {
    $notificationCount = 0;
}
?>
<aside class="app-rail" aria-label="Main navigation">
    <a class="app-rail-logo" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>" title="LivingSpring">LS</a>
    <nav class="app-rail-nav">
        <a class="app-rail-link<?= $layoutMode === 'feed' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>" title="School feed">
            <i class="bi bi-house-door-fill"></i>
        </a>
        <a class="app-rail-link<?= $layoutMode === 'explore' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/communities'), ENT_QUOTES, 'UTF-8'); ?>" title="Communities">
            <i class="bi bi-grid-3x3-gap-fill"></i>
        </a>
        <a class="app-rail-link<?= $layoutMode === 'people' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>" title="People">
            <i class="bi bi-people-fill"></i>
        </a>
        <a class="app-rail-link" href="<?= htmlspecialchars(url('/search'), ENT_QUOTES, 'UTF-8'); ?>" title="Search">
            <i class="bi bi-search"></i>
        </a>
        <a class="app-rail-link" href="<?= htmlspecialchars(url($layoutMode === 'room' ? '/chat?room=' . urlencode((string)($activeRoom['slug'] ?? '')) . '#dmRequestsList' : '/feed#dmRequestsList'), ENT_QUOTES, 'UTF-8'); ?>" title="DM requests">
            <i class="bi bi-chat-dots-fill"></i>
            <?php if ($dmInboxCount > 0): ?><span class="app-rail-badge"><?= (int) $dmInboxCount; ?></span><?php endif; ?>
        </a>
    </nav>
    <div class="app-rail-foot">
        <a class="app-rail-link<?= $notificationCount > 0 ? ' has-badge' : ''; ?>" href="<?= htmlspecialchars(url('/notifications'), ENT_QUOTES, 'UTF-8'); ?>" title="Notifications">
            <i class="bi bi-bell-fill"></i>
            <?php if ($notificationCount > 0): ?><span class="app-rail-badge"><?= min(9, $notificationCount); ?><?= $notificationCount > 9 ? '+' : ''; ?></span><?php endif; ?>
        </a>
        <a class="app-rail-link<?= $layoutMode === 'profile' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" title="Profile">
            <i class="bi bi-person-circle"></i>
        </a>
        <a class="app-rail-link<?= $layoutMode === 'settings' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/settings'), ENT_QUOTES, 'UTF-8'); ?>" title="Settings">
            <i class="bi bi-gear-fill"></i>
        </a>
        <?php if (has_moderator_privileges()): ?>
            <a class="app-rail-link" href="<?= htmlspecialchars(url(is_admin() ? '/admin/auth' : '/admin/chats'), ENT_QUOTES, 'UTF-8'); ?>" title="<?= is_admin() ? 'Central admin' : 'Class Rep'; ?>">
                <i class="bi bi-shield-fill"></i>
            </a>
        <?php endif; ?>
    </div>
</aside>
