<?php
$shell = array_merge(app_shell_data('profile'), [
    'title' => 'Profile | LivingSpring',
    'errors' => $errors ?? [],
    'success' => $success ?? null,
    'classOptions' => $classOptions ?? [],
    'infoDefaultTab' => 'account',
]);
extract($shell);
$userName = $user['name'] ?? 'Unknown User';
$words = preg_split('/\s+/', trim((string) $userName)) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
}
$isOnline = (bool) ((int) ($user['is_online'] ?? 1));
$hasEditErrors = !empty($errors['name']) || !empty($errors['email']) || !empty($errors['class_name']) || !empty($errors['room_name']) || !empty($errors['status']) || !empty($errors['headline']) || !empty($errors['bio']) || !empty($errors['password']) || !empty($errors['database']);
$avatarPath = $user['avatar_path'] ?? '';
require view_path('partials.header');
require view_path('partials/app-shell-open');
?>
        <div class="shell-page shell-page--profile" id="profile-page">
            <header class="shell-page-header">
                <div>
                    <span class="feed-kicker">Your account</span>
                    <h2>Profile</h2>
                    <p>How others see you across LivingSpring.</p>
                </div>
                <a class="profile-edit-trigger auth-button button-reset" href="#edit-profile"><i class="bi bi-pencil-square"></i> Edit</a>
            </header>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars((string) $errors['database'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="profile-box profile-box--shell">
                <?php if ($avatarPath !== ''): ?>
                    <img class="profile-avatar-image" src="<?= htmlspecialchars((string) $avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php else: ?>
                    <div class="profile-avatar-large"><?= htmlspecialchars($initials !== '' ? $initials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <h1><?= htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8'); ?></h1>
                <span class="presence-badge <?= $isOnline ? 'is-online' : 'is-offline'; ?>">
                    <span class="presence-dot" aria-hidden="true"></span>
                    <?= $isOnline ? 'Online' : 'Offline'; ?>
                </span>
                <p class="profile-subtitle">
                    <?= htmlspecialchars((string) ucfirst((string) ($user['role'] ?? 'student')), ENT_QUOTES, 'UTF-8'); ?>
                    <?= !empty($user['class_name']) ? ' · ' . htmlspecialchars((string) $user['class_name'], ENT_QUOTES, 'UTF-8') : ''; ?>
                </p>
                <?php if (!empty($user['headline'])): ?>
                    <p class="profile-headline"><?= htmlspecialchars((string) $user['headline'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if (has_moderator_privileges()): ?>
                    <a class="profile-edit-trigger" href="<?= htmlspecialchars(url(is_admin() ? '/admin/auth' : '/admin/chats'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-shield-lock"></i> <?= is_admin() ? 'Central Admin' : 'Class Rep'; ?></a>
                <?php endif; ?>
            </div>

            <?php if (!empty($user['bio'])): ?>
                <div class="profile-bio-card">
                    <strong>About</strong>
                    <p><?= nl2br(htmlspecialchars((string) $user['bio'], ENT_QUOTES, 'UTF-8')); ?></p>
                </div>
            <?php endif; ?>

            <div class="profile-details">
                <div class="profile-detail"><span>Full name</span><strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="profile-detail"><span>Email</span><strong><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="profile-detail"><span>Class</span><strong><?= htmlspecialchars((string) ($user['class_name'] ?? 'JSS1'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="profile-detail"><span>Main room</span><strong><?= htmlspecialchars((string) ($user['room_name'] ?? 'General Room'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="profile-detail"><span>Joined</span><strong><?= htmlspecialchars((string) ($user['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
            </div>

            <form class="logout-form" method="POST" action="<?= htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <button class="auth-button button-reset" type="submit">Logout</button>
            </form>
        </div>

        <section id="edit-profile" class="profile-modal<?= $hasEditErrors ? ' is-open' : ''; ?>">
            <a class="profile-modal-backdrop" href="#profile-page" aria-label="Close edit profile"></a>
            <div class="profile-modal-card">
                <div class="profile-modal-header">
                    <div>
                        <span class="profile-modal-kicker">Edit profile</span>
                        <h2>Edit profile</h2>
                    </div>
                    <a class="profile-modal-close" href="#profile-page" aria-label="Close"><i class="bi bi-x-lg"></i></a>
                </div>
                <form class="auth-form" method="POST" action="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
                    <?= csrf_input(); ?>
                    <div class="field">
                        <label for="profile-name">Full name</label>
                        <input id="profile-name" name="name" type="text" value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($errors['name'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="profile-email">Email</label>
                        <input id="profile-email" name="email" type="email" value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($errors['email'])): ?><small class="field-error"><?= htmlspecialchars((string) $errors['email'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="profile-headline">Headline</label>
                        <input id="profile-headline" name="headline" type="text" maxlength="140" value="<?= htmlspecialchars((string) ($user['headline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field">
                        <label for="profile-bio">About</label>
                        <textarea id="profile-bio" name="bio" rows="4"><?= htmlspecialchars((string) ($user['bio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="field">
                        <label for="profile-class">Class</label>
                        <select id="profile-class" name="class_name" class="field-select">
                            <?php foreach ($classOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= (($user['class_name'] ?? 'JSS1') === $option) ? 'selected' : ''; ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="profile-room">Main room</label>
                        <input id="profile-room" name="room_name" type="text" value="<?= htmlspecialchars((string) ($user['room_name'] ?? 'General Room'), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <input type="hidden" name="status" value="<?= $isOnline ? 'Online' : 'Offline'; ?>">
                    <div class="field">
                        <label for="profile-password">New password</label>
                        <input id="profile-password" name="password" type="password" placeholder="Leave blank to keep current">
                    </div>
                    <div class="field">
                        <label for="profile-avatar">Profile photo</label>
                        <input id="profile-avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="profile-actions">
                        <a class="profile-cancel-link" href="#profile-page">Cancel</a>
                        <button class="auth-button button-reset" type="submit">Save changes</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <aside class="chat-info" id="chatInfoPanel" aria-label="Account and settings">
        <?php require view_path('profile/partials/profile-aside'); ?>
    </aside>
<?php require view_path('partials/app-shell-close'); ?>
<?php require view_path('partials.footer'); ?>
