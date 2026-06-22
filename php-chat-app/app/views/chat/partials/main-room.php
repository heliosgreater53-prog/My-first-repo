<?php
$activeRoomName = (string)($activeRoom['name'] ?? 'Room');
$activeRoomDescription = (string)($activeRoom['description'] ?? '');
$activeRoomAccent = (string)($activeRoom['accent_color'] ?? '#14b8a6');
$onlineCount = count(is_array($onlineUsers ?? null) ? $onlineUsers : []);
$roomSlug = (string)($activeRoom['slug'] ?? '');
$todayLabel = '';
?>



<?php if (!empty($pinnedMessages)): ?>
    <section class="room-pinned-bar" aria-label="Pinned messages">
        <?php foreach ($pinnedMessages as $pin): ?>
            <article class="room-pinned-item">
                <i class="bi bi-pin-angle-fill"></i>
                <div>
                    <strong><?= htmlspecialchars((string) ($pin['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?= htmlspecialchars(mb_substr((string) ($pin['body'] ?? ''), 0, 120), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<div class="conversation-feed conversation-feed--chat" id="conversationFeedRoom" data-room-slug="<?= htmlspecialchars($roomSlug, ENT_QUOTES, 'UTF-8'); ?>" data-layout="room">

    <?php if ($messages === []): ?>
        <section class="empty-feed-card empty-feed-card--room">
            <div class="empty-feed-icon"><i class="bi bi-chat-dots"></i></div>
            <h3>Start the conversation</h3>
            <p>Say hello, ask a question, or share something with everyone in this room.</p>
        </section>
    <?php endif; ?>
    <?php foreach ($messages as $message): ?>
        <?php
        $messageDateLabel = date('l, F j', strtotime((string)$message['created_at']));
        if ($messageDateLabel !== $todayLabel) {
            $todayLabel = $messageDateLabel;
            echo '<div class="message-date">' . htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $viewer = $user;
        require view_path('chat/partials/message-room');
        ?>
    <?php endforeach; ?>
</div>

<?php require view_path('chat/partials/composer'); ?>
