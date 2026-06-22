<section class="feed-header">
    <div class="feed-header-copy">
        <span class="feed-kicker">Browse</span>
        <h2>Communities</h2>
        <p>Join class studios, school hubs, and direct chats. Password-protected rooms need a key from your teacher or admin.</p>
    </div>
</section>

<div class="explore-grid" id="exploreGrid">
    <?php foreach ($rooms as $room): ?>
        <?php
        $roomSlug = (string)($room['slug'] ?? '');
        $scopeLabel = 'Public';
        if (($room['scope'] ?? 'public') === 'class') {
            $scopeLabel = (string)($room['class_name'] ?? 'Class');
        } elseif (($room['scope'] ?? 'public') === 'direct') {
            $scopeLabel = 'Direct';
        }
        $hasPassword = !empty($room['password_hash']);
        $unreadCount = (int)($room['unread_count'] ?? 0);
        $lastMsg = $room['last_message_at'] ?? '';
        ?>
        <article class="explore-card" style="--card-accent: <?= htmlspecialchars((string)($room['accent_color'] ?? '#14b8a6'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="explore-card-accent"></span>
            <a class="explore-card-body" href="<?= htmlspecialchars(url('/chat?room=' . urlencode($roomSlug)), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="explore-card-top">
                    <h3><?= htmlspecialchars((string)$room['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php if ($hasPassword): ?><i class="bi bi-lock-fill" title="Password protected"></i><?php endif; ?>
                </div>
                <p><?= htmlspecialchars((string)($room['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="explore-card-meta">
                    <span class="explore-scope"><?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span><?= (int) ($room['member_count'] ?? 0); ?> members</span>
                    <?php if ($lastMsg): ?>
                        <span><?= htmlspecialchars(date('M j', strtotime((string) $lastMsg)), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if ($unreadCount > 0): ?>
                        <span class="unread-badge"><?= (int) $unreadCount; ?> new</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php if (!$hasPassword && empty($room['is_member']) && ($room['scope'] ?? '') === 'public'): ?>
                <form class="explore-join-form" method="POST" action="<?= htmlspecialchars(url('/communities/join'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($roomSlug, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="explore-join-btn">Join room</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
