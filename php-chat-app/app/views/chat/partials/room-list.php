<?php
$rooms = $rooms ?? [];
$layoutMode = $layoutMode ?? 'feed';
$activeRoomSlug = (string)($activeRoom['slug'] ?? '');
?>
<?php foreach ($rooms as $room): ?>
    <?php
    $roomSlug = (string)($room['slug'] ?? '');
    $isActiveRoom = $layoutMode === 'room' && $roomSlug === $activeRoomSlug;
    $hasPassword = !empty($room['password_hash']);
    $isLockedForUser = $hasPassword && !has_admin_privileges() && empty($room['is_member']);
    $preview = $isLockedForUser ? 'Password required' : trim((string)($room['last_message_body'] ?? $room['description'] ?? 'No messages yet'));
    if (mb_strlen($preview) > 48) {
        $preview = mb_substr($preview, 0, 48) . '…';
    }
    $unreadCount = $isLockedForUser ? 0 : (int)($room['unread_count'] ?? 0);
    $timeLabel = ($room['last_message_at'] ?? '') !== '' ? date('g:i A', strtotime((string)$room['last_message_at'])) : '';
    ?>
    <a class="room-row<?= $isActiveRoom ? ' is-active' : ''; ?><?= $unreadCount > 0 ? ' has-unread' : ''; ?>"
        href="<?= htmlspecialchars(url('/chat?room=' . urlencode($roomSlug)), ENT_QUOTES, 'UTF-8'); ?>"
        data-room-slug="<?= htmlspecialchars($roomSlug, ENT_QUOTES, 'UTF-8'); ?>"
        style="--room-accent: <?= htmlspecialchars((string)($room['accent_color'] ?? '#0d9488'), ENT_QUOTES, 'UTF-8'); ?>"
        <?= $hasPassword ? ' data-password-protected="1"' : ''; ?>>
        <span class="room-row-accent"></span>
        <div class="room-row-body">
            <div class="room-row-top">
                <strong><?= htmlspecialchars((string)$room['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if ($hasPassword): ?><i class="bi bi-lock-fill" style="font-size:11px;color:var(--text-faint);"></i><?php endif; ?>
            </div>
            <p class="room-row-preview"><?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="room-row-meta">
            <?php if ($timeLabel !== ''): ?><span><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
            <?php if ($unreadCount > 0): ?><span class="unread-badge"><?= (int) $unreadCount; ?></span><?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
