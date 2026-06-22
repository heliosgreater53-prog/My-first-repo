<?php $message = $message ?? []; ?>
<div class="message-attachment post-attachment">
    <?php if (($message['attachment_type'] ?? '') === 'image'): ?>
        <a href="<?= htmlspecialchars((string)$message['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <img src="<?= htmlspecialchars((string)$message['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Attachment">
        </a>
    <?php elseif (($message['attachment_type'] ?? '') === 'audio'): ?>
        <audio controls src="<?= htmlspecialchars((string)$message['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>"></audio>
    <?php else: ?>
        <a class="attachment-link" href="<?= htmlspecialchars((string)$message['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <i class="bi bi-paperclip"></i> <?= htmlspecialchars((string)($message['attachment_name'] ?? 'Download file'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
    <?php endif; ?>
</div>
