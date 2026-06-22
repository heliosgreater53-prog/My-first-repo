<?php
$post = $post ?? [];
$viewer = $viewer ?? [];
$isMine = (int)($post['user_id'] ?? 0) === (int)($viewer['id'] ?? 0);
$postName = (string)($post['name'] ?? 'User');
$postWords = preg_split('/\s+/', trim($postName)) ?: [];
$postInitials = '';
foreach (array_slice($postWords, 0, 2) as $word) {
    $postInitials .= strtoupper(substr($word, 0, 1));
}
$canDelete = $isMine || has_admin_privileges();
$reactions = is_array($post['reactions'] ?? null) ? $post['reactions'] : [];
$bodyText = !empty($post['deleted_at']) ? '[deleted]' : (string)($post['body'] ?? '');
$postRole = (string)($post['user_role'] ?? $post['role'] ?? '');
$isCentralAdmin = $postRole === 'admin';
$isModerator = in_array($postRole, ['class_rep', 'moderator'], true);
$showRoleBadge = $isCentralAdmin || $isModerator;
?>
<article class="post-card<?= $isMine ? ' is-mine' : ''; ?>"
    data-post-id="<?= (int)($post['id'] ?? 0); ?>"
    data-post-author="<?= htmlspecialchars($postName, ENT_QUOTES, 'UTF-8'); ?>">
    <header class="post-header">
        <?php if (!empty($post['avatar_path'])): ?>
            <div class="avatar-with-role-badge">
                <img class="post-avatar" src="<?= htmlspecialchars((string)$post['avatar_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php if ($showRoleBadge): ?>
                    <span class="avatar-role-badge avatar-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                        <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="avatar-with-role-badge">
                <div class="post-avatar post-avatar-fallback"><?= htmlspecialchars($postInitials !== '' ? $postInitials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if ($showRoleBadge): ?>
                    <span class="avatar-role-badge avatar-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                        <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="post-meta">
            <strong><?= htmlspecialchars($isMine ? 'You' : $postName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ($showRoleBadge): ?>
                <span class="post-role-badge post-role-badge--<?= $isCentralAdmin ? 'admin' : 'mod'; ?>" title="<?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?>">
                    <i class="bi <?= $isCentralAdmin ? 'bi-patch-check-fill' : 'bi-shield-fill'; ?>"></i>
                    <span><?= $isCentralAdmin ? 'Central Admin' : 'Moderator'; ?></span>
                </span>
            <?php endif; ?>
            <?php if (($post['post_type'] ?? '') === 'assignment'): ?>
                <span class="post-type-badge post-type-badge--assignment">Assignment</span>
            <?php elseif (($post['post_type'] ?? '') === 'announcement'): ?>
                <span class="post-type-badge post-type-badge--announcement">Announcement</span>
            <?php endif; ?>
            <span><?= htmlspecialchars(date('g:i A', strtotime((string)$post['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </header>
    <section class="post-body">
        <div class="post-text"><?= format_message_body($bodyText); ?></div>

        <?php if (($post['post_type'] ?? '') === 'assignment' && !empty($post['due_at'])): ?>
            <div class="post-due">Due: <?= htmlspecialchars(date('M j, g:i A', strtotime((string)$post['due_at'])), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!empty($post['attachment_path']) && !empty($post['attachment_type'])): ?>
            <div class="post-attachment">
                <?php if (($post['attachment_type'] ?? '') === 'image'): ?>
                    <a href="<?= htmlspecialchars((string)$post['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                        <img class="post-attachment-image" src="<?= htmlspecialchars((string)$post['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Attachment">
                    </a>
                <?php elseif (($post['attachment_type'] ?? '') === 'audio'): ?>
                    <audio class="post-attachment-audio" controls src="<?= htmlspecialchars((string)$post['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>"></audio>
                <?php else: ?>
                    <a class="attachment-link" href="<?= htmlspecialchars((string)$post['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                        <i class="bi bi-paperclip"></i> <?= htmlspecialchars((string)($post['attachment_name'] ?? 'Download file'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <footer class="post-footer">
        <div class="post-interactions">
            <button class="post-action-btn js-post-like" type="button" title="Like" data-post-id="<?= (int)($post['id'] ?? 0); ?>">
                <i class="bi bi-heart"></i>
                <span class="js-post-like-count">0</span>
            </button>
            <button class="post-action-btn js-post-comments" type="button" title="Comment" data-post-id="<?= (int)($post['id'] ?? 0); ?>" aria-controls="postCommentsModal">
                <i class="bi bi-chat-dots"></i>
                <span class="js-post-comment-count">0</span>
            </button>

            <?php if ($canDelete && empty($post['deleted_at'])): ?>
                <form method="POST" action="<?= htmlspecialchars(url('/posts/delete'), ENT_QUOTES, 'UTF-8'); ?>" class="js-confirm-delete">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="post_id" value="<?= (int)($post['id'] ?? 0); ?>">
                    <button class="post-menu-item is-danger" type="submit"><i class="bi bi-trash"></i> Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </footer>
</article>