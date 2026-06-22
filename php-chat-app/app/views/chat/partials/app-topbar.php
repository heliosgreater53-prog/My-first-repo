<?php
$layoutMode = $layoutMode ?? 'feed';
$titles = [
    'feed' => ['School feed', 'Posts from your communities'],
    'room' => [(string)($activeRoom['name'] ?? 'Room'), (string)($activeRoom['description'] ?? 'Channel chat')],
    'explore' => ['Communities', 'Browse and join spaces'],
    'people' => ['People', 'Find classmates and message'],
    'profile' => ['Your profile', 'Account and preferences'],
    'settings' => ['Settings', 'Preferences and notifications'],
];
$top = $titles[$layoutMode] ?? ['LivingSpring', ''];
$showContextToggle = in_array($layoutMode, ['feed', 'room'], true);
$showInspectorToggle = !empty($showAside);
$notificationCount = (int) ($notificationCount ?? 0);
?>
<header class="app-topbar">
    <div class="app-topbar-title">
        <h1><?= htmlspecialchars($top[0], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($top[1] !== ''): ?>
            <p><?= htmlspecialchars($top[1], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
    <div class="app-topbar-actions">
        <?php if ($showContextToggle): ?>
            <button type="button" class="app-topbar-btn context-toggle" id="contextToggleBtn" aria-controls="appContext" aria-expanded="false" title="Rooms">
                <i class="bi bi-list-ul"></i>
            </button>
        <?php endif; ?>
        <?php if ($layoutMode === 'people'): ?>
            <a class="app-topbar-btn app-topbar-search-link" href="#directoryFilters" title="Search users">
                <i class="bi bi-funnel"></i>
            </a>
        <?php endif; ?>
        <a class="app-topbar-btn" href="<?= htmlspecialchars(url('/search'), ENT_QUOTES, 'UTF-8'); ?>" title="Search"><i class="bi bi-search"></i></a>

        <?php if (auth_user() !== null): ?>
            <?php
            $avatarPath = $user['avatar_path'] ?? (auth_user()['avatar_path'] ?? '');
            $avatarAlt = htmlspecialchars((string) (auth_user()['name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
            ?>
            <a class="app-topbar-user" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" title="Your profile" aria-label="Your profile">
                <?php if (!empty($avatarPath)): ?>
                    <img class="app-topbar-user-avatar" src="<?= htmlspecialchars((string) $avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= $avatarAlt; ?>">
                <?php else: ?>
                    <span class="app-topbar-user-avatar-fallback"><?= htmlspecialchars(mb_substr((string) (auth_user()['name'] ?? 'U'), 0, 1), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <a class="app-topbar-btn<?= $notificationCount > 0 ? ' has-badge' : ''; ?>" href="<?= htmlspecialchars(url('/notifications'), ENT_QUOTES, 'UTF-8'); ?>" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($notificationCount > 0): ?><span class="badge-dot"></span><?php endif; ?>
        </a>
        <?php if ($showInspectorToggle): ?>
            <button type="button" class="app-topbar-btn" id="mobilePanelBtn" aria-controls="chatInfoPanel" title="Activity panel">
                <i class="bi bi-layout-sidebar-inset-reverse"></i>
            </button>
        <?php endif; ?>
    </div>
</header>
