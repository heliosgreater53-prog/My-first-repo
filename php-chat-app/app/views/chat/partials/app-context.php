<?php
$showRoomList = $showRoomList ?? true;
if (!$showRoomList) {
    return;
}
?>
<aside class="app-context" id="appContext" aria-label="Rooms">
    <div class="app-context-head">
        <h2>Rooms</h2>
        <p>Jump into a conversation</p>
        <div class="app-context-search">
            <i class="bi bi-search"></i>
            <input type="search" id="roomSearchInput" placeholder="Filter rooms…" autocomplete="off">
        </div>
    </div>
    <div class="app-context-rooms" id="roomList">
        <?php require view_path('chat/partials/room-list'); ?>
    </div>
</aside>
