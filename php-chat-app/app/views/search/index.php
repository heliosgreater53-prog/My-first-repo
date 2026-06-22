<?php require view_path('partials.header'); ?>
<main class="auth-page" style="min-height:100vh;padding:24px;">
    <section class="auth-card" style="max-width:720px;margin:0 auto;">
        <div class="auth-main" style="width:100%;">
            <h1>Search messages</h1>
            <p>Find posts across rooms you can access.</p>
            <form class="auth-form" method="GET" action="<?= htmlspecialchars(url('/search'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="search-q">Keywords</label>
                    <input id="search-q" name="q" type="search" value="<?= htmlspecialchars((string) ($term ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search message text…" autofocus>
                </div>
                <button class="auth-button button-reset" type="submit">Search</button>
            </form>

            <?php if (($term ?? '') !== ''): ?>
                <div class="admin-message-list" style="margin-top:20px;">
                    <?php foreach ($results as $row): ?>
                        <article class="admin-message-item">
                            <div>
                                <strong><?= htmlspecialchars((string) ($row['name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars((string) ($row['room_name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars(date('M j, g:i A', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <p><?= htmlspecialchars((string) ($row['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <a class="admin-text-link" href="<?= htmlspecialchars(url('/chat?room=' . urlencode((string) ($row['room_slug'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>">Open room</a>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($results === []): ?>
                        <p class="admin-empty">No messages matched your search.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p style="margin-top:16px;"><a class="auth-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">← Back to feed</a></p>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
