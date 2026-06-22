<?php
$message = $message ?? [];
$viewer = $viewer ?? [];
$isMine = (int)($message['user_id'] ?? 0) === (int)($viewer['id'] ?? 0);
$messageName = (string)($message['name'] ?? 'User');
$messageWords = preg_split('/\s+/', trim($messageName)) ?: [];
$messageInitials = '';
foreach (array_slice($messageWords, 0, 2) as $word) {
    $messageInitials .= strtoupper(substr($word, 0, 1));
}
$replyBody = trim((string)($message['reply_body'] ?? ''));
$canDelete = $isMine || has_admin_privileges();
$messageRoomSlug = (string)($message['room_slug'] ?? ($activeRoom['slug'] ?? 'home'));
$messageRoomName = (string)($message['room_name'] ?? 'Community');
$reactions = is_array($message['reactions'] ?? null) ? $message['reactions'] : [];
$bodyText = !empty($message['deleted_at']) ? '[deleted]' : (string)($message['body'] ?? '');
$messageRole = (string)($message['user_role'] ?? $message['role'] ?? '');
$isCentralAdmin = $messageRole === 'admin';
$isModerator = in_array($messageRole, ['class_rep', 'moderator'], true);
$showRoleBadge = $isCentralAdmin || $isModerator;
?>
<article class="post-card<?= $isMine ? ' is-mine' : ''; ?>"
    data-message-id="<?= (int)($message['id'] ?? 0); ?>"
    data-message-author="<?= htmlspecialchars($messageName, ENT_QUOTES, 'UTF-8'); ?>"
    data-message-body="<?= htmlspecialchars((string)($message['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
    data-message-room="<?= htmlspecialchars($messageRoomSlug, ENT_QUOTES, 'UTF-8'); ?>"
    data-message-can-edit="<?= $isMine && empty($message['deleted_at']) ? '1' : '0'; ?>">
    <header class="post-header">
        <?php if (!empty($message['avatar_path'])): ?>
            <div class="avatar-with-role-badge">
                <img class="post-avatar" src="<?= htmlspecialchars((string)$message['avatar_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php if ($showRoleBadge): ?>
                    <span class="avatar-role-badge avatar-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                        <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="avatar-with-role-badge">
                <div class="post-avatar post-avatar-fallback"><?= htmlspecialchars($messageInitials !== '' ? $messageInitials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if ($showRoleBadge): ?>
                    <span class="avatar-role-badge avatar-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                        <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="post-meta">
            <strong><?= htmlspecialchars($isMine ? 'You' : $messageName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ($showRoleBadge): ?>
                <span class="post-role-badge post-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                    <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    <span><?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?></span>
                </span>
            <?php endif; ?>
            <?php if (($message['post_type'] ?? '') === 'assignment'): ?>
                <span class="post-type-badge post-type-badge--assignment">Assignment</span>
            <?php elseif (($message['post_type'] ?? '') === 'announcement' || str_starts_with($bodyText, '[Announcement]')): ?>
                <span class="post-type-badge post-type-badge--announcement">Announcement</span>
            <?php endif; ?>
            <span><a href="<?= htmlspecialchars(url('/chat?room=' . urlencode($messageRoomSlug)), ENT_QUOTES, 'UTF-8'); ?>">#<?= htmlspecialchars($messageRoomName, ENT_QUOTES, 'UTF-8'); ?></a> · <?= htmlspecialchars(date('g:i A', strtotime((string)$message['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php if ((int)($message['is_pinned'] ?? 0) === 1): ?>
            <span class="post-pin" title="Pinned"><i class="bi bi-pin-angle-fill"></i></span>
        <?php endif; ?>
    </header>
    <section class="post-body">
        <?php if ($replyBody !== ''): ?>
            <div class="post-context">
                <strong><?= htmlspecialchars((string)($message['reply_name'] ?? 'Reply'), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?= htmlspecialchars($replyBody, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <div class="post-text"><?= format_message_body($bodyText); ?></div>
        <?php if (empty($message['deleted_at']) && !empty($message['attachment_path'])): ?>
            <?php require view_path('chat/partials/attachment'); ?>
        <?php endif; ?>
    </section>
    <footer class="post-footer">
        <div class="post-interactions">
            <?php foreach (['like' => 'bi-heart', 'heart' => 'bi-heart-fill', 'laugh' => 'bi-emoji-laughing'] as $reactionKey => $icon): ?>
                <form method="POST" action="<?= htmlspecialchars(url('/chat/messages/react'), ENT_QUOTES, 'UTF-8'); ?>" class="interaction-form">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="message_id" value="<?= (int)($message['id'] ?? 0); ?>">
                    <input type="hidden" name="room" value="<?= htmlspecialchars($messageRoomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="reaction" value="<?= htmlspecialchars($reactionKey, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="post-action-btn" type="submit" title="<?= htmlspecialchars($reactionKey, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi <?= $icon; ?>"></i><span><?= (int)($reactions[$reactionKey] ?? 0); ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
            <button class="post-action-btn js-reply-post" type="button" title="Reply"><i class="bi bi-chat-dots"></i><span><?= (int)($message['reply_count'] ?? 0); ?></span></button>
            <details class="post-menu">
                <summary class="post-action-btn" title="More"><i class="bi bi-three-dots"></i></summary>
                <div class="post-menu-panel">
                    <button class="post-menu-item js-copy-post" type="button"><i class="bi bi-copy"></i> Copy</button>
                    <?php if ($isMine && empty($message['deleted_at'])): ?>
                        <button class="post-menu-item js-edit-post" type="button"><i class="bi bi-pencil"></i> Edit</button>
                    <?php endif; ?>
                    <form method="POST" action="<?= htmlspecialchars(url('/chat/messages/pin'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="room" value="<?= htmlspecialchars($messageRoomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="message_id" value="<?= (int)($message['id'] ?? 0); ?>">
                        <button class="post-menu-item" type="submit"><i class="bi bi-pin-angle"></i> Pin</button>
                    </form>
                    <?php if (empty($message['deleted_at'])): ?>
                        <form method="POST" action="<?= htmlspecialchars(url('/chat/messages/report'), ENT_QUOTES, 'UTF-8'); ?>">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="room" value="<?= htmlspecialchars($messageRoomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="message_id" value="<?= (int)($message['id'] ?? 0); ?>">
                            <input type="hidden" name="reason" value="Reported from feed">
                            <button class="post-menu-item" type="submit"><i class="bi bi-flag"></i> Report</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canDelete && empty($message['deleted_at'])): ?>
                        <form method="POST" action="<?= htmlspecialchars(url('/chat/messages/delete'), ENT_QUOTES, 'UTF-8'); ?>" class="js-confirm-delete">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="room" value="<?= htmlspecialchars($messageRoomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="message_id" value="<?= (int)($message['id'] ?? 0); ?>">
                            <button class="post-menu-item is-danger" type="submit"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    </footer>
</article>
