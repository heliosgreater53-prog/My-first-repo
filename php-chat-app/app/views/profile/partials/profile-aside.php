<div class="profile-aside-card">
    <div class="profile-aside-header">
        <h3>Account</h3>
        <p class="muted">Manage your personal settings and preferences.</p>
    </div>

    <div class="profile-aside-section">
        <a class="profile-aside-row" href="#edit-profile">
            <i class="bi bi-pencil-square"></i>
            <div>
                <strong>Edit profile</strong>
                <span class="muted">Change name, bio, avatar and more</span>
            </div>
        </a>

        <a class="profile-aside-row" href="<?= htmlspecialchars(url('/settings'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-gear"></i>
            <div>
                <strong>Account settings</strong>
                <span class="muted">Privacy, notifications and preferences</span>
            </div>
        </a>

        <a class="profile-aside-row" href="<?= htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>" onclick="event.preventDefault(); document.getElementById('aside-logout-form').submit();">
            <i class="bi bi-box-arrow-right text-danger"></i>
            <div>
                <strong class="text-danger">Logout</strong>
                <span class="muted">Sign out of your account</span>
            </div>
        </a>

        <form id="aside-logout-form" method="POST" action="<?= htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>" style="display:none;">
            <?= csrf_input(); ?>
        </form>
    </div>

    <div class="profile-aside-section">
        <h4>Quick info</h4>
        <div class="profile-aside-info">
            <div><strong>Joined</strong><span><?= htmlspecialchars((string) ($user['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div><strong>Role</strong><span><?= htmlspecialchars((string) ucfirst((string) ($user['role'] ?? 'student')), ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
    </div>
</div>
