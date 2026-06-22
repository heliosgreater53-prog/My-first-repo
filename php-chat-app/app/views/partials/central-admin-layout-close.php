<?php
// Central Admin layout closer
// Keeps DOM structure consistent with central-admin-layout-open.php
?>
            </section>
        </div>
    </div>
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>
    <?php require view_path('partials.admin-mobile-nav'); ?>
</main>
<script src="<?= htmlspecialchars(asset('js/admin.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>

