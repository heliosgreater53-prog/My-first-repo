<?php
$layoutMode = $layoutMode ?? 'feed';
$showRoomList = $showRoomList ?? true;
$showAside = $showAside ?? true;
$dmInboxCount = count(is_array($dmInboxRequests ?? null) ? $dmInboxRequests : []);
$isRoomView = ($layoutMode === 'room');
$activePath = isset($_SERVER['REQUEST_URI']) ? strtok((string)$_SERVER['REQUEST_URI'], '?') : '';
$isUsersPage = $activePath === '/users';
$isProfilePage = $activePath === '/profile';
$isFeedPage = $activePath === '/feed' || $activePath === '';

?>
<nav class="mobile-bottom-nav<?= $showRoomList ? '' : ' mobile-bottom-nav--compact'; ?>" aria-label="Main navigation">
    <a class="mobile-nav-item<?= ($isFeedPage ? ' is-active' : (($layoutMode === 'feed') ? ' is-active' : ''))); ?>" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">

        <i class="bi bi-house-door"></i>
        <span>Feed</span>
    </a>
    <a class="mobile-nav-item<?= ($isUsersPage ? ' is-active' : (($layoutMode === 'explore') ? ' is-active' : ''))); ?>" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>">

        <i class="bi bi-people"></i>
        <span>Spaces</span>
    </a>

    <?php if ($showRoomList): ?>
        <button type="button" class="mobile-nav-item<?= $isRoomView ? ' is-active' : ''; ?>" id="mobileRoomsBtn" aria-controls="mobileRoomsSheet" aria-expanded="false">
            <i class="bi bi-hash"></i>
            <span>Rooms</span>
        </button>
    <?php endif; ?>
    <?php if ($layoutMode === 'people'): ?>
        <a class="mobile-nav-item<?= $isUsersPage ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>">

            <i class="bi bi-people"></i>
            <span>People</span>
        </a>
    <?php elseif (!empty($showAside)): ?>
        <button type="button" class="mobile-nav-item" id="mobilePanelBtn" aria-controls="chatInfoPanel" aria-expanded="false">
            <i class="bi bi-envelope"></i>
            <span>DMs</span>
            <?php if ($dmInboxCount > 0): ?><b class="mobile-nav-badge"><?= (int) $dmInboxCount; ?></b><?php endif; ?>
        </button>
    <?php else: ?>
        <a class="mobile-nav-item" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-envelope"></i>
            <span>DMs</span>
        </a>
    <?php endif; ?>
    <a class="mobile-nav-item<?= ($layoutMode === 'profile') ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-person-circle"></i>
        <span>Me</span>
    </a>
</nav>

<?php if ($showRoomList): ?>
    <div class="mobile-rooms-sheet" id="mobileRoomsSheet" aria-hidden="true">
        <div class="mobile-sheet-backdrop" id="mobileRoomsBackdrop" aria-hidden="true"></div>
        <div class="mobile-sheet-panel" role="dialog" aria-labelledby="mobileRoomsTitle">
            <header class="mobile-sheet-header">
                <h2 id="mobileRoomsTitle">Your rooms</h2>
                <button type="button" class="mobile-sheet-close" id="mobileRoomsClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </header>
            <div class="mobile-sheet-body rail-room-stack" id="mobileRoomList">
                <?php require view_path('chat/partials/room-list'); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
