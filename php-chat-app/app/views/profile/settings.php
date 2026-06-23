<?php
$title = $title ?? 'Settings | LivingSpring';
$themePref = $themePref ?? (string)($user['theme_preference'] ?? 'system');
$reduceMotion = (bool)($reduceMotion ?? false);
$notificationsEnabled = (bool)($notificationsEnabled ?? true);
$browserNotificationsEnabled = (bool)($browserNotificationsEnabled ?? true);
$mentionNotificationsEnabled = (bool)($mentionNotificationsEnabled ?? true);
$dmNotificationsEnabled = (bool)($dmNotificationsEnabled ?? true);
$compactUi = (bool)($compactUi ?? false);

require view_path('partials.header');

$layoutData = array_merge(app_shell_data('settings'), [
    'title' => $title,
    'errors' => $errors ?? [],
    'success' => $success ?? null,
    'infoDefaultTab' => 'account',
]);
extract($layoutData);
?>

<?php require view_path('partials/app-shell-open'); ?>

<div class="shell-page shell-page--settings" id="settings-page">
    <header class="shell-page-header">
        <div>
            <span class="feed-kicker">Preferences</span>
            <h2>Settings</h2>
            <p>Control your notifications, appearance, and comfort options.</p>
        </div>
    </header>

    <?php if (!empty($errors['database'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars((string)$errors['database'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(url('/settings'), ENT_QUOTES, 'UTF-8'); ?>" class="settings-form">
        <?= csrf_input(); ?>

        <section class="settings-grid">
            <div class="settings-card">
                <h3 class="settings-card-title">Notifications</h3>

                <label class="settings-toggle">
                    <input type="checkbox" name="notifications_enabled" value="1" <?= $notificationsEnabled ? 'checked' : ''; ?>>
                    <span>
                        <strong>Receive notifications</strong>
                        <small>Allow LivingSpring to show notification counts and alerts.</small>
                    </span>
                </label>

                <label class="settings-toggle">
                    <input type="checkbox" name="browser_notifications_enabled" value="1" <?= $browserNotificationsEnabled ? 'checked' : ''; ?>>
                    <span>
                        <strong>Browser popups</strong>
                        <small>Ask this browser for permission to show desktop notifications.</small>
                    </span>
                </label>

                <label class="settings-toggle">
                    <input type="checkbox" name="mention_notifications_enabled" value="1" <?= $mentionNotificationsEnabled ? 'checked' : ''; ?>>
                    <span>
                        <strong>Mentions</strong>
                        <small>Create alerts when someone mentions your name with @.</small>
                    </span>
                </label>

                <label class="settings-toggle">
                    <input type="checkbox" name="dm_notifications_enabled" value="1" <?= $dmNotificationsEnabled ? 'checked' : ''; ?>>
                    <span>
                        <strong>DM requests</strong>
                        <small>Keep direct message requests visible in your sidebar badge.</small>
                    </span>
                </label>
            </div>

            <div class="settings-card">
                <h3 class="settings-card-title">Appearance</h3>

                <div class="field">
                    <div class="themes-link-row">
                        <label for="themePref">Theme</label>
                        <a class="settings-link-inline" href="<?= htmlspecialchars(url('/settings/themes'), ENT_QUOTES, 'UTF-8'); ?>">Themes</a>
                    </div>
                    <select id="themePref" name="theme_pref" class="compose-select">
                        <option value="system" <?= ($themePref === 'system') ? 'selected' : ''; ?>>Use device setting</option>
                        <option value="light" <?= ($themePref === 'light') ? 'selected' : ''; ?>>Default Light</option>
                        <option value="dark" <?= ($themePref === 'dark') ? 'selected' : ''; ?>>Default Dark</option>

                        <option value="default_light" <?= ($themePref === 'default_light') ? 'selected' : ''; ?>>Default Light (explicit)</option>
                        <option value="default_dark" <?= ($themePref === 'default_dark') ? 'selected' : ''; ?>>Default Dark (explicit)</option>
                        <option value="midnight_blue" <?= ($themePref === 'midnight_blue') ? 'selected' : ''; ?>>Midnight Blue</option>
                        <option value="forest_green" <?= ($themePref === 'forest_green') ? 'selected' : ''; ?>>Forest Green</option>
                        <option value="sunset_orange" <?= ($themePref === 'sunset_orange') ? 'selected' : ''; ?>>Sunset Orange</option>
                        <option value="lavender_purple" <?= ($themePref === 'lavender_purple') ? 'selected' : ''; ?>>Lavender Purple</option>
                        <option value="rose_pink" <?= ($themePref === 'rose_pink') ? 'selected' : ''; ?>>Rose Pink</option>
                        <option value="cyber_neon" <?= ($themePref === 'cyber_neon') ? 'selected' : ''; ?>>Cyber Neon</option>
                    </select>
                </div>


                <label class="settings-toggle">
                    <input type="checkbox" name="compact_ui" value="1" <?= $compactUi ? 'checked' : ''; ?>>
                    <span>
                        <strong>Compact layout</strong>
                        <small>Use tighter spacing in sidebars and repeated lists.</small>
                    </span>
                </label>

                <label class="settings-toggle">
                    <input type="checkbox" name="reduce_motion" value="1" <?= $reduceMotion ? 'checked' : ''; ?>>
                    <span>
                        <strong>Reduce motion</strong>
                        <small>Minimize animated transitions and hover movement.</small>
                    </span>
                </label>
            </div>
        </section>

        <div class="settings-savebar">
            <a class="settings-link-inline" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>">Back to profile</a>
            <button class="auth-button button-reset" type="submit"><i class="bi bi-save"></i> Save settings</button>
        </div>
    </form>
</div>

<?php require view_path('partials/app-shell-close'); ?>
<?php require view_path('partials.footer'); ?>
