<?php
$communityCount = count(is_array($rooms ?? null) ? $rooms : []);
$publicCommunityCount = 0;
$classCommunityCount = 0;
$trendingRooms = [];
foreach ($rooms as $roomOption) {
    if (($roomOption['scope'] ?? 'public') === 'class') {
        $classCommunityCount++;
    } elseif (($roomOption['scope'] ?? 'public') === 'public') {
        $publicCommunityCount++;
    }
    if (count($trendingRooms) < 5) {
        $trendingRooms[] = $roomOption;
    }
}
$dmInboxCount = count(is_array($dmInboxRequests ?? null) ? $dmInboxRequests : []);
$infoDefaultTab = $dmInboxCount > 0 ? 'requests' : 'people';
?>
<div class="info-panel">
    <div class="aside-card">
        <span class="aside-kicker">Discover</span>
        <h3>Jump into a room</h3>
        <div class="pulse-grid pulse-grid--compact">
            <div class="pulse-card"><strong><?= (int) $communityCount; ?></strong><span>total</span></div>
            <div class="pulse-card"><strong><?= (int) $publicCommunityCount; ?></strong><span>public</span></div>
            <div class="pulse-card"><strong><?= (int) $classCommunityCount; ?></strong><span>class</span></div>
        </div>
    </div>

    <div class="aside-card">
        <h3>Active communities</h3>
        <div class="trend-list">
            <?php foreach ($trendingRooms as $roomOption): ?>
                <?php $trendSlug = (string)($roomOption['slug'] ?? ''); ?>
                <a class="trend-item" href="<?= htmlspecialchars(url('/chat?room=' . urlencode($trendSlug)), ENT_QUOTES, 'UTF-8'); ?>" style="--trend-accent: <?= htmlspecialchars((string)($roomOption['accent_color'] ?? '#14b8a6'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span></span>
                    <div>
                        <strong><?= htmlspecialchars((string)($roomOption['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?= htmlspecialchars((string)($roomOption['last_message_body'] ?? $roomOption['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php require view_path('chat/partials/aside-shared'); ?>
</div>
