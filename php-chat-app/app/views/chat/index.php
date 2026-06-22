<?php require view_path('partials.header'); ?>
<?php
$layoutMode = $layoutMode ?? 'feed';
$requiresRoomPassword = (bool)($requiresRoomPassword ?? false);
$showAside = ($layoutMode !== 'explore');
$showDrawerToggle = $showAside;
?>
<?php require view_path('partials/app-shell-open'); ?>
        <?php if ($requiresRoomPassword): ?>
            <div class="room-lock-panel">
                <div class="room-lock-icon"><i class="bi bi-lock-fill"></i></div>
                <div>
                    <h3>Password required</h3>
                    <p>Enter the room password to view messages in <?= htmlspecialchars((string)($activeRoom['name'] ?? 'this room'), ENT_QUOTES, 'UTF-8'); ?>.</p>
                </div>
                <form class="room-lock-form" method="POST" action="<?= htmlspecialchars(url('/chat/join'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="slug" value="<?= htmlspecialchars((string)($activeRoom['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input name="password" type="password" placeholder="Room password" autocomplete="current-password" required>
                    <button class="auth-button button-reset" type="submit"><i class="bi bi-unlock"></i> Unlock</button>
                </form>
            </div>
        <?php else: ?>
            <?php
            if ($layoutMode === 'explore') {
                require view_path('chat/partials/main-explore');
            } elseif ($layoutMode === 'room') {
                require view_path('chat/partials/main-room');
            } else {
                require view_path('chat/partials/main-feed');
            }
            ?>
        <?php endif; ?>
<?php require view_path('partials/app-shell-close'); ?>
<script src="<?= htmlspecialchars(asset('js/chat.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?= htmlspecialchars(asset('js/room-filter.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php require view_path('partials.footer'); ?>
