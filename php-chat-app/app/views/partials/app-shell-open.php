<?php
$layoutMode = $layoutMode ?? 'feed';
$showRoomList = $showRoomList ?? !in_array($layoutMode, ['people', 'profile'], true);
$showAside = $showAside ?? ($layoutMode !== 'explore');
$showContextPanel = in_array($layoutMode, ['feed', 'room'], true);
$frameClass = 'app-frame';
if ($showAside) {
    $frameClass .= ' has-inspector';
}
// Add a shell class for room/feed layouts to enable chat-shell specific styles
if (in_array($layoutMode, ['room', 'feed'], true)) {
    $frameClass .= ' chat-shell--room';
}
?>
<div class="<?= htmlspecialchars($frameClass, ENT_QUOTES, 'UTF-8'); ?>" id="chatShell" data-layout="<?= htmlspecialchars($layoutMode, ENT_QUOTES, 'UTF-8'); ?>">
    <?php require view_path('chat/partials/app-icon-rail'); ?>
    <?php if ($showContextPanel): ?>
        <?php require view_path('chat/partials/app-context'); ?>
    <?php endif; ?>
    <div class="app-workspace">
        <?php require view_path('chat/partials/app-topbar'); ?>
        <main class="app-main" id="chatMain">
