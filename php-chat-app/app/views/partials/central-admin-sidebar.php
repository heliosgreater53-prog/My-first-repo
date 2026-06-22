<aside class="admin-sidebar" id="adminSidebar" aria-label="Central admin navigation">
    <div class="admin-sidebar-brand">
        <div class="rail-mark" aria-hidden="true">LS</div>
        <div>
            <strong>LivingSpring</strong>
            <span>Central Admin</span>
        </div>
    </div>

    <nav class="admin-sidebar-nav" aria-label="Central admin sections">
        <p class="admin-sidebar-label">Menu</p>
        <a class="admin-sidebar-link<?= $activeAdminPage === 'dashboard' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/central-admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a class="admin-sidebar-link<?= $activeAdminPage === 'users' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/central-admin/users'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-people"></i>
            <span>Users</span>
        </a>
        <a class="admin-sidebar-link<?= $activeAdminPage === 'rooms' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/central-admin/rooms'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-door-open"></i>
            <span>Rooms</span>
        </a>
        <a class="admin-sidebar-link<?= $activeAdminPage === 'audit' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/central-admin/audit-logs'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-journal-text"></i>
            <span>Audit logs</span>
        </a>
        <a class="admin-sidebar-link<?= $activeAdminPage === 'settings' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/central-admin/settings'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="admin-sidebar-actions">
        <a class="admin-sidebar-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-arrow-left"></i>
            <span>Back to app</span>
        </a>
        <a class="admin-sidebar-link" href="<?= htmlspecialchars(url('/admin/auth'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-shield-lock"></i>
            <span>Re-verify access</span>
        </a>
    </div>
</aside>
