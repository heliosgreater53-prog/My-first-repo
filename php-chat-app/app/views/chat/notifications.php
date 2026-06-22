<?php require view_path('partials.header'); ?>
<main class="auth-page" style="min-height:100vh;padding:24px;">
    <section class="auth-card" style="max-width:640px;margin:0 auto;">
        <div class="auth-main" style="width:100%;">
            <h1>Notifications</h1>
            <p>Mentions and alerts from across LivingSpring.</p>
            <div class="admin-message-list">
                <?php foreach ($notifications as $note): ?>
                    <article class="admin-message-item">
                        <div>
                            <strong><?= htmlspecialchars((string) ($note['from_name'] ?? 'System'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?= htmlspecialchars(date('M j, g:i A', strtotime((string) ($note['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <p><?= htmlspecialchars((string) ($note['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($note['room_slug'])): ?>
                            <a class="admin-text-link" href="<?= htmlspecialchars(url('/chat?room=' . urlencode((string) $note['room_slug'])), ENT_QUOTES, 'UTF-8'); ?>">Open</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if ($notifications === []): ?>
                    <p class="admin-empty">No notifications yet.</p>
                <?php endif; ?>
            </div>
            <p style="margin-top:16px;"><a class="auth-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">← Back to feed</a></p>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
