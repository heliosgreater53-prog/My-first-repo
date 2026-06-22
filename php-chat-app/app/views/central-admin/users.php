<?php
$activeAdminPage = 'users';
require view_path('partials.header');
$filters = $filters ?? ['q' => '', 'class_name' => '', 'ban_state' => ''];
$isCentralAdmin = true;
require view_path('partials.central-admin-layout-open');
?>
            <div class="admin-page-head">
                <div>
                    <span class="admin-kicker">Accounts</span>
                    <h1>Central user management</h1>
                    <p>Create students, assign class reps, and manage account status across the whole platform.</p>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-head"><div><h2>Bulk import</h2><p>CSV columns: name, email, class, password</p></div></div>
                <form method="POST" action="<?= htmlspecialchars(url('/central-admin/users/import'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
                    <?= csrf_input(); ?>
                    <input type="file" name="csv" accept=".csv,text/csv" required>
                    <button class="auth-button button-reset" type="submit" style="margin-top:10px;">Import CSV</button>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Create account</h2>
                        <p>Add a student or another class rep without leaving the panel.</p>
                    </div>
                </div>
                <form class="admin-create-form" method="POST" action="<?= htmlspecialchars(url('/central-admin/users/create'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <div class="field">
                        <label>Name</label>
                        <input name="name" type="text" placeholder="Full name">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input name="email" type="email" placeholder="student@example.com">
                    </div>
                    <div class="field">
                        <label>Class</label>
                        <select name="class_name" class="field-select">
                            <?php foreach ($classOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Access level</label>
                        <select name="role" class="field-select">
                            <?php foreach ($roleOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($option === 'admin' ? 'Central Admin' : ($option === 'class_rep' ? 'Class Rep' : 'Student'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Temporary password</label>
                        <input name="password" type="password" placeholder="At least 6 characters">
                    </div>
                    <button class="auth-button button-reset" type="submit">Create account</button>
                </form>
            </section>

            <section class="admin-panel">
                <form class="admin-user-filter" method="GET" action="<?= htmlspecialchars(url('/central-admin/users'), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field">
                        <label>Search</label>
                        <input name="q" type="search" placeholder="Name or email" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field">
                        <label>Class</label>
                        <select name="class_name" class="field-select">
                            <option value="">All classes</option>
                            <?php foreach ($classOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= ($filters['class_name'] ?? '') === $option ? 'selected' : ''; ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Ban status</label>
                        <select name="ban_state" class="field-select">
                            <option value="">All accounts</option>
                            <option value="unbanned" <?= ($filters['ban_state'] ?? '') === 'unbanned' ? 'selected' : ''; ?>>Unbanned</option>
                            <option value="banned" <?= ($filters['ban_state'] ?? '') === 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                    </div>
                    <button class="auth-button button-reset" type="submit">Filter users</button>
                </form>
            </section>

            <div class="admin-list">
                <?php foreach ($users as $adminUser): ?>
                    <?php
                    $isBanned = (int) $adminUser['is_active'] !== 1;
                    $isMuted = !empty($adminUser['is_muted']);
                    $canMuteUser = ($adminUser['role'] ?? 'student') !== 'admin';
                    $isAdminOnline = (bool) ((int) ($adminUser['is_online'] ?? 0));
                    $adminStatus = $isAdminOnline ? 'Online' : 'Offline';
                    ?>
                    <form class="admin-card" method="POST" action="<?= htmlspecialchars(url('/central-admin/users/update'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $adminUser['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="admin-card-head">
                            <div>
                                <div class="admin-user-title">
                                    <strong><?= htmlspecialchars((string) $adminUser['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="presence-badge <?= $isAdminOnline ? 'is-online' : 'is-offline'; ?>">
                                        <span class="presence-dot" aria-hidden="true"></span>
                                        <?= htmlspecialchars($adminStatus, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <p><?= htmlspecialchars((string) $adminUser['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="admin-account-state">
                                <span class="admin-state-pill <?= $isBanned ? 'is-banned' : ''; ?>"><?= $isBanned ? 'Banned' : 'Unbanned'; ?></span>
                                <?php if ($isMuted): ?>
                                    <span class="admin-state-pill is-banned">Muted until <?= htmlspecialchars(date('M j, g:i A', strtotime((string) $adminUser['muted_until'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="admin-grid">
                            <div class="field">
                                <label>Access level</label>
                                <select name="role" class="field-select">
                                    <?php foreach ($roleOptions as $option): ?>
                                        <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= ($adminUser['role'] === $option || ($option === 'class_rep' && in_array((string) $adminUser['role'], ['class_rep', 'moderator'], true))) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($option === 'admin' ? 'Central Admin' : ($option === 'class_rep' ? 'Class Rep' : 'Student'), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Presence</label>
                                <input type="text" value="<?= htmlspecialchars($adminStatus, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                <input type="hidden" name="status" value="<?= htmlspecialchars($adminStatus, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label>Class</label>
                                <select name="class_name" class="field-select">
                                    <?php foreach ($classOptions as $option): ?>
                                        <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $adminUser['class_name'] === $option ? 'selected' : ''; ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Main room</label>
                                <input name="room_name" type="text" value="<?= htmlspecialchars((string) $adminUser['room_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label>Headline</label>
                                <input name="headline" type="text" maxlength="140" value="<?= htmlspecialchars((string) ($adminUser['headline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label>Temporary password</label>
                                <input name="password" type="password" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="field field-span-2">
                                <label>About</label>
                                <textarea name="bio" rows="3" placeholder="Short profile note"><?= htmlspecialchars((string) ($adminUser['bio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <div class="admin-actions">
                            <button class="auth-button button-reset" type="submit">Save user</button>
                        </div>
                    </form>
                    <?php if ($canMuteUser): ?>
                        <?php if ($isMuted): ?>
                            <form class="admin-ban-action-form" method="POST" action="<?= htmlspecialchars(url('/central-admin/users/unmute'), ENT_QUOTES, 'UTF-8'); ?>">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $adminUser['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/central-admin/users'), ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="admin-toggle-button unban" type="submit"><span></span>Unmute chat</button>
                            </form>
                        <?php else: ?>
                            <form class="admin-ban-action-form" method="POST" action="<?= htmlspecialchars(url('/central-admin/users/mute'), ENT_QUOTES, 'UTF-8'); ?>">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $adminUser['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/central-admin/users'), ENT_QUOTES, 'UTF-8'); ?>">
                                <select name="duration" class="field-select" required>
                                    <option value="1h">Mute 1 hour</option>
                                    <option value="24h">Mute 24 hours</option>
                                    <option value="7d">Mute 7 days</option>
                                </select>
                                <input name="reason" type="text" placeholder="Reason, optional">
                                <button class="admin-toggle-button ban" type="submit"><span></span>Mute chat</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <form class="admin-ban-action-form" method="POST" action="<?= htmlspecialchars(url('/central-admin/users/ban-toggle'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $adminUser['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="is_active" value="<?= $isBanned ? '1' : '0'; ?>">
                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/central-admin/users'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="admin-toggle-button <?= $isBanned ? 'unban' : 'ban'; ?>" type="submit">
                            <span></span>
                            <?= $isBanned ? 'Unban account' : 'Ban account'; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            <?php if (($userPages ?? 1) > 1): ?>
                <nav class="admin-pagination" aria-label="Users pages">
                    <?php for ($p = 1; $p <= (int) $userPages; $p++): ?>
                        <a class="<?= ($userPage ?? 1) === $p ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(url('/central-admin/users?page=' . $p . '&q=' . urlencode((string) ($filters['q'] ?? '')) . '&class_name=' . urlencode((string) ($filters['class_name'] ?? '')) . '&ban_state=' . urlencode((string) ($filters['ban_state'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
<?php require view_path('partials.central-admin-layout-close'); ?>
<?php require view_path('partials.footer'); ?>

