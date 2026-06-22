<?php
$shell = array_merge(app_shell_data('people'), [
    'title' => 'People | LivingSpring',
    'users' => $users ?? [],
    'term' => $term ?? '',
    'selectedClass' => $selectedClass ?? '',
    'selectedPresence' => $selectedPresence ?? '',
    'classOptions' => $classOptions ?? [],
    'dmRequestStatusMap' => $dmRequestStatusMap ?? [],
    'viewerId' => $viewerId ?? 0,
    'infoDefaultTab' => 'people',
    'blockStatusMap' => $blockStatusMap ?? [],
]);
extract($shell);
$directoryQuery = [];
if ((string) $term !== '') {
    $directoryQuery['q'] = (string) $term;
}
if ((string) $selectedClass !== '') {
    $directoryQuery['class'] = (string) $selectedClass;
}
if ((string) $selectedPresence !== '') {
    $directoryQuery['presence'] = (string) $selectedPresence;
}
$directoryUrl = url('/users' . ($directoryQuery !== [] ? '?' . http_build_query($directoryQuery) : ''));
$hasDirectoryFilters = (string) $term !== '' || (string) $selectedClass !== '' || (string) $selectedPresence !== '';
require view_path('partials.header');
require view_path('partials/app-shell-open');
?>
        <div class="shell-page">
            <header class="shell-page-header">
                <div>
                    <span class="feed-kicker">Directory</span>
                    <h2>People</h2>
                    <p>Find classmates and admins, then request a direct message.</p>
                </div>
            </header>

            <form class="directory-search-panel" id="directoryFilters" method="GET" action="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="directory-q">Search</label>
                    <div class="directory-search-box">
                        <i class="bi bi-search"></i>
                        <input id="directory-q" name="q" type="search" value="<?= htmlspecialchars((string) $term, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search users by name or email">
                    </div>
                </div>
                <div class="field">
                    <label for="directory-class">Class</label>
                    <select id="directory-class" name="class" class="field-select">
                        <option value="">All classes</option>
                        <?php foreach ($classOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedClass === $option ? 'selected' : ''; ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="directory-presence">Status</label>
                    <select id="directory-presence" name="presence" class="field-select">
                        <option value="">Online and offline</option>
                        <option value="online" <?= $selectedPresence === 'online' ? 'selected' : ''; ?>>Online only</option>
                        <option value="offline" <?= $selectedPresence === 'offline' ? 'selected' : ''; ?>>Offline only</option>
                    </select>
                </div>
                <div class="directory-actions">
                    <button class="auth-button button-reset" type="submit"><i class="bi bi-funnel"></i><span>Filter users</span></button>
                    <?php if ($hasDirectoryFilters): ?>
                        <a class="button-reset directory-clear-link" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="directory-results-summary">
                <strong><?= count($users); ?></strong>
                <span><?= count($users) === 1 ? 'validated user found' : 'validated users found'; ?></span>
            </div>

            <div class="directory-grid">
                <?php if (empty($users)): ?>
                    <div class="directory-empty">
                        <i class="bi bi-search"></i>
                        <strong>No users found</strong>
                        <p>Try a different name, email, or class filter.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($users as $directoryUser): ?>
                    <?php
                    $isDirectoryOnline = (bool) ((int) ($directoryUser['is_online'] ?? 0));
                    $directoryUserId = (int) ($directoryUser['id'] ?? 0);
                    $dmStatus = is_array($dmRequestStatusMap) ? ($dmRequestStatusMap[$directoryUserId] ?? null) : null;
                    $isSelf = (int) $viewerId === $directoryUserId;
                    $directoryRole = (string) ($directoryUser['role'] ?? 'student');
                    $directoryRoleLabel = $directoryRole === 'admin' ? 'Central Admin' : (in_array($directoryRole, ['class_rep', 'moderator'], true) ? 'Class Rep' : ucfirst($directoryRole));
                    $dirInitials = '';
                    foreach (array_slice(preg_split('/\s+/', trim((string)($directoryUser['name'] ?? ''))) ?: [], 0, 2) as $part) {
                        $dirInitials .= strtoupper(substr($part, 0, 1));
                    }
                    ?>
                    <article class="directory-card">
                        <div class="directory-card-person">
                            <?php if (!empty($directoryUser['avatar_path'])): ?>
                                <img class="directory-avatar" src="<?= htmlspecialchars((string) $directoryUser['avatar_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                            <?php else: ?>
                                <div class="directory-avatar directory-avatar-fallback"><?= htmlspecialchars($dirInitials !== '' ? $dirInitials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <div>
                                <div class="directory-card-top">
                                    <strong><?= htmlspecialchars((string) $directoryUser['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="directory-role"><?= htmlspecialchars($directoryRoleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <p class="directory-email"><?= htmlspecialchars((string) $directoryUser['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        <?php if (!empty($directoryUser['headline'])): ?>
                            <p class="directory-headline"><?= htmlspecialchars((string) $directoryUser['headline'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <div class="directory-meta">
                            <span><?= htmlspecialchars((string) $directoryUser['class_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="directory-validated"><i class="bi bi-check-circle-fill"></i> Validated</span>
                            <span class="presence-badge <?= $isDirectoryOnline ? 'is-online' : 'is-offline'; ?>">
                                <span class="presence-dot" aria-hidden="true"></span>
                                <?= $isDirectoryOnline ? 'Online' : 'Offline'; ?>
                            </span>
                        </div>
                        <?php if ($isSelf): ?>
                            <span class="dm-state-badge">This is you</span>
                        <?php elseif (is_array($dmStatus) && ($dmStatus['status'] ?? '') === 'pending'): ?>
                            <span class="dm-state-badge"><?= ($dmStatus['direction'] ?? '') === 'sent' ? 'Request sent' : 'Waiting for your response'; ?></span>
                        <?php elseif (is_array($dmStatus) && ($dmStatus['status'] ?? '') === 'accepted'): ?>
                            <?php $dmRoomSlug = (string) ($dmStatus['room_slug'] ?? ''); ?>
                            <a class="auth-button button-reset directory-chat-link" href="<?= htmlspecialchars(url('/chat' . ($dmRoomSlug !== '' ? '?room=' . urlencode($dmRoomSlug) : '')), ENT_QUOTES, 'UTF-8'); ?>">Open chat</a>
                        <?php else: ?>
                            <?php
                                $peerBlockMeta = is_array($blockStatusMap) ? ($blockStatusMap[(int) $directoryUserId] ?? null) : null;
                                $viewerBlockedPeer = (bool) (($peerBlockMeta['viewer_blocked'] ?? false) === true);
                                $peerBlockedViewer = (bool) (($peerBlockMeta['peer_blocked'] ?? false) === true);
                            ?>

                            <?php if ($viewerBlockedPeer || $peerBlockedViewer): ?>
                                <?php if ($viewerBlockedPeer && !$peerBlockedViewer): ?>
                                    <form class="directory-chat-form" method="POST" action="<?= htmlspecialchars(url('/users/unblock'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $directoryUserId; ?>">
                                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($directoryUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="button-reset directory-block-btn" type="submit">Unblock</button>
                                    </form>
                                <?php else: ?>
                                    <span class="dm-state-badge">Blocked</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <form class="directory-chat-form" method="POST" action="<?= htmlspecialchars(url('/users/dm-request'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="peer_id" value="<?= (int) $directoryUserId; ?>">
                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($directoryUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="auth-button button-reset" type="submit">Request DM</button>
                                </form>

                                <form class="directory-chat-form" method="POST" action="<?= htmlspecialchars(url('/users/block'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Block this user? They cannot DM you.');">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $directoryUserId; ?>">
                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($directoryUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="button-reset directory-block-btn" type="submit">Block</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
<?php require view_path('partials/app-shell-close'); ?>
<?php require view_path('partials.footer'); ?>
