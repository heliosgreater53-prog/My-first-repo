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
$roomSlug = (string)($activeRoom['slug'] ?? '');
$reactions = is_array($message['reactions'] ?? null) ? $message['reactions'] : [];
$bodyText = !empty($message['deleted_at']) ? '[deleted]' : (string)($message['body'] ?? '');
$messageRole = (string)($message['user_role'] ?? $message['role'] ?? '');
$isCentralAdmin = $messageRole === 'admin';
$isModerator = in_array($messageRole, ['class_rep', 'moderator'], true);
$showRoleBadge = $isCentralAdmin || $isModerator;
?>
<div class="message-cluster<?= $isMine ? ' mine' : ''; ?>"
    data-message-id="<?= (int)($message['id'] ?? 0); ?>"
    data-message-author="<?= htmlspecialchars($messageName, ENT_QUOTES, 'UTF-8'); ?>"
    data-message-body="<?= htmlspecialchars((string)($message['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
    data-message-room="<?= htmlspecialchars($roomSlug, ENT_QUOTES, 'UTF-8'); ?>"
    data-message-can-edit="<?= $isMine && empty($message['deleted_at']) ? '1' : '0'; ?>">
    <?php if (!$isMine): ?>
        <div class="avatar-with-role-badge">
            <?php if (!empty($message['avatar_path'])): ?>
                <img class="cluster-avatar" src="<?= htmlspecialchars((string)$message['avatar_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
            <?php else: ?>
                <div class="cluster-avatar cluster-avatar-fallback"><?= htmlspecialchars($messageInitials !== '' ? $messageInitials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($showRoleBadge): ?>
                <span class="avatar-role-badge avatar-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                    <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="message-bundle">
        <?php if (!$isMine): ?>
            <div class="message-meta">
                <strong><?= htmlspecialchars($messageName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if ($showRoleBadge): ?>
                    <span class="message-role-badge message-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                        <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    </span>
                <?php endif; ?>
                <span><?= htmlspecialchars(date('g:i A', strtotime((string)$message['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <?php if ((int)($message['is_pinned'] ?? 0) === 1): ?>
            <div class="pin-snippet"><i class="bi bi-pin-angle-fill"></i> Pinned</div>
        <?php endif; ?>
        <?php if ($replyBody !== ''): ?>
            <div class="reply-snippet">
                <strong><?= htmlspecialchars((string)($message['reply_name'] ?? 'Reply'), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?= htmlspecialchars($replyBody, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <div class="message-bubble <?= $isMine ? 'sent' : 'received'; ?>">
            <?= format_message_body($bodyText); ?>
            <?php if (empty($message['deleted_at']) && !empty($message['attachment_path'])): ?>
                <?php require view_path('chat/partials/attachment'); ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($reactions)): ?>
            <div class="reaction-row">
                <?php foreach ($reactions as $key => $count): ?>
                    <?php if ((int)$count > 0): ?>
                        <span class="reaction-chip"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'); ?> <?= (int)$count; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="message-actions">
            <form method="POST" action="<?= htmlspecialchars(url('/chat/messages/react'), ENT_QUOTES, 'UTF-8'); ?>" class="interaction-form">
                <?= csrf_input(); ?>
                <input type="hidden" name="message_id" value="<?= (int)($message['id'] ?? 0); ?>">
                <input type="hidden" name="room" value="<?= htmlspecialchars($roomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="reaction" value="like">
                <button class="message-action-button" type="submit" title="Like"><i class="bi bi-heart"></i></button>
            </form>
            <button class="message-action-button js-reply-post" type="button" title="Reply"><i class="bi bi-arrow-return-left"></i></button>
            <?php if ($isMine && empty($message['deleted_at'])): ?>
                <button class="message-action-button js-edit-post" type="button" title="Edit"><i class="bi bi-pencil"></i></button>
            <?php endif; ?>
            <?php if ($canDelete && empty($message['deleted_at'])): ?>
                <form method="POST" action="<?= htmlspecialchars(url('/chat/messages/delete'), ENT_QUOTES, 'UTF-8'); ?>" class="js-confirm-delete d-inline">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="room" value="<?= htmlspecialchars($roomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="message_id" value="<?= (int)($message['id'] ?? 0); ?>">
                    <button class="message-action-button text-danger" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
