<?php
$activeRoomName = (string)($activeRoom['name'] ?? 'Room');
$activeRoomDescription = (string)($activeRoom['description'] ?? '');
$activeRoomAccent = (string)($activeRoom['accent_color'] ?? '#14b8a6');
$dmInboxCount = count(is_array($dmInboxRequests ?? null) ? $dmInboxRequests : []);
$infoDefaultTab = $dmInboxCount > 0 ? 'requests' : 'people';
?>
<div class="info-panel">
    <div class="room-hero room-hero--compact" style="--hero-accent: <?= htmlspecialchars($activeRoomAccent, ENT_QUOTES, 'UTF-8'); ?>">
        <span class="room-eyebrow">This room</span>
        <h2><?= htmlspecialchars($activeRoomName, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?= htmlspecialchars($activeRoomDescription, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="room-hero-stats">
            <div class="hero-stat">
                <span>Access</span>
                <strong><?= htmlspecialchars((string)(($activeRoom['scope'] ?? 'public') === 'class' ? (($activeRoom['class_name'] ?? 'Class') . ' only') : 'Members'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="hero-stat">
                <span>Messages</span>
                <strong><?= (int) count(is_array($messages ?? null) ? $messages : []); ?></strong>
            </div>
        </div>
    </div>

    <?php require view_path('chat/partials/aside-shared'); ?>
</div>
