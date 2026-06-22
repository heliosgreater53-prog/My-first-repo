<?php
$layoutMode = $layoutMode ?? 'feed';
$currentPath = $currentPath ?? '/feed';
$isHomeFeed = ($layoutMode === 'feed');
$showRoomList = $showRoomList ?? !in_array($layoutMode, ['people', 'profile', 'feed'], true);

$dmInboxCount = count(is_array($dmInboxRequests ?? null) ? $dmInboxRequests : []);
?>
<aside class="chat-rail">
    <div class="rail-brand rail-brand-compact">
        <div class="rail-mark" aria-hidden="true">LS</div>
        <div>
            <h1>LivingSpring</h1>
            <p><?= htmlspecialchars((string)($user['class_name'] ?? 'JSS1'), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars(ucfirst((string)($user['role'] ?? 'student')), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <nav class="app-nav" aria-label="Primary">
        <a class="app-nav-link<?= $isHomeFeed ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-house-door"></i><span>School feed</span>
        </a>
        <a class="app-nav-link<?= ($layoutMode === 'explore') ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/communities'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-grid-3x3-gap"></i><span>Communities</span>
        </a>
        <a class="app-nav-link<?= $layoutMode === 'people' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-people"></i><span>People</span>
        </a>
        <a class="app-nav-link" href="<?= htmlspecialchars(url($layoutMode === 'room' ? '/chat?room=' . urlencode((string)($activeRoom['slug'] ?? '')) . '#dmRequestsList' : '/feed#dmRequestsList'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-envelope"></i><span>DM requests</span>
            <?php if ($dmInboxCount > 0): ?><b class="nav-badge"><?= (int) $dmInboxCount; ?></b><?php endif; ?>
        </a>
        <a class="app-nav-link<?= $layoutMode === 'profile' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-person-circle"></i><span>Profile</span>
        </a>
    </nav>

    <?php if ($isHomeFeed): ?>
        <a class="create-post-button" href="#messageInput"><i class="bi bi-plus-lg"></i><span>New post</span></a>
    <?php endif; ?>

    <?php if ($showRoomList): ?>
    <div class="rail-section-title">Your communities</div>
    <div class="rail-room-stack rail-room-stack-scroll" id="roomList">
        <?php require view_path('chat/partials/room-list'); ?>
    </div>
    <?php else: ?>
        <p class="rail-hint">Use the feed or pick a community to start chatting.</p>
    <?php endif; ?>
</aside>
