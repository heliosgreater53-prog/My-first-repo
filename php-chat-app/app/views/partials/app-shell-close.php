        </main>
    </div>

    <?php if (!empty($showAside)): ?>
        <aside class="app-inspector" id="chatInfoPanel" aria-label="Activity">
            <div class="app-inspector-inner">
                <?php
                if ($layoutMode === 'room') {
                    require view_path('chat/partials/aside-room');
                } elseif ($layoutMode === 'people') {
                    require view_path('users/partials/directory-aside');
                } else {
                    require view_path('chat/partials/aside-feed');
                }
                ?>
            </div>
        </aside>
        <div class="app-drawer-backdrop" id="panelDrawerBackdrop" aria-hidden="true"></div>
        <button type="button" class="panel-fab" id="panelDrawerToggle" aria-controls="chatInfoPanel" aria-expanded="false">
            <i class="bi bi-people-fill"></i>
            <span>Activity</span>
        </button>
    <?php endif; ?>

    <div class="app-drawer-backdrop" id="contextBackdrop" aria-hidden="true"></div>
    <?php require view_path('chat/partials/mobile-dock'); ?>
</div>
<?php if (auth_user() !== null): ?>
<script src="<?= htmlspecialchars(asset('js/realtime.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<script src="<?= htmlspecialchars(asset('js/app-shell.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
