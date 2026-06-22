<?php
$userName = $user['name'] ?? 'Unknown User';
$words = preg_split('/\s+/', trim((string)$userName)) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
}
$avatarPath = $user['avatar_path'] ?? '';
$isCurrentUserOnline = (bool)((int)($user['is_online'] ?? 1));
$roleName = (string)($user['role'] ?? 'student');
$roleLabel = $roleName === 'admin' ? 'Central Admin' : (in_array($roleName, ['class_rep', 'moderator'], true) ? 'Class Rep' : ucfirst($roleName));
$dmInboxCount = count(is_array($dmInboxRequests ?? null) ? $dmInboxRequests : []);
if ((int) ($user['dm_notifications_enabled'] ?? 1) !== 1 || (int) ($user['notifications_enabled'] ?? 1) !== 1) {
    $dmInboxCount = 0;
}
$infoDefaultTab = $infoDefaultTab ?? ($dmInboxCount > 0 ? 'requests' : 'people');
?>
<div class="info-tabs" id="infoTabs" data-default-tab="<?= htmlspecialchars($infoDefaultTab, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="info-tab-list" role="tablist" aria-label="Sidebar">
        <button class="info-tab-button<?= $infoDefaultTab === 'people' ? ' is-active' : ''; ?>" type="button" data-info-tab="people">
            <i class="bi bi-people"></i><span>Online</span>
        </button>
        <button class="info-tab-button<?= $infoDefaultTab === 'requests' ? ' is-active' : ''; ?>" type="button" data-info-tab="requests">
            <i class="bi bi-envelope"></i><span>DMs</span><b id="dmRequestCount"><?= (int) $dmInboxCount; ?></b>
        </button>
        <button class="info-tab-button<?= $infoDefaultTab === 'account' ? ' is-active' : ''; ?>" type="button" data-info-tab="account">
            <i class="bi bi-person-circle"></i><span>Account</span>
        </button>
    </div>

    <div class="info-tab-panel<?= $infoDefaultTab === 'people' ? ' is-active' : ''; ?>" data-info-panel="people">
        <div class="online-users-section">
            <div class="online-users-head">
                <h3>Online here</h3>
                <span id="onlineUsersCount"><?= (int) count(is_array($onlineUsers ?? null) ? $onlineUsers : []); ?></span>
            </div>
            <div class="online-users-list" id="onlineUsersList">
                <?php if (!empty($onlineUsers) && is_array($onlineUsers)): ?>
                    <?php foreach ($onlineUsers as $onlineUser): ?>
                        <?php
                        $userInitials = '';
                        foreach (array_slice(preg_split('/\s+/', trim((string)($onlineUser['name'] ?? ''))) ?: [], 0, 2) as $part) {
                            $userInitials .= strtoupper(substr($part, 0, 1));
                        }
                        ?>
                        <form method="POST" action="<?= htmlspecialchars(url('/users/dm-request'), ENT_QUOTES, 'UTF-8'); ?>" class="online-user-form">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="peer_id" value="<?= (int)($onlineUser['id'] ?? 0); ?>">

                            <button type="submit" class="online-user-item" aria-label="Message <?= htmlspecialchars((string)($onlineUser['name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span style="position:absolute;left:-9999px;">Send DM request</span>

                                <?php if (!empty($onlineUser['avatar_path'])): ?>
                                    <img class="online-user-avatar" src="<?= htmlspecialchars((string)$onlineUser['avatar_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                <?php else: ?>
                                    <div class="online-user-avatar-fallback"><?= htmlspecialchars($userInitials ?: 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <div class="online-user-info">
                                    <strong><?= htmlspecialchars((string)($onlineUser['name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?= htmlspecialchars((string)($onlineUser['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <span class="presence-dot online"></span>
                            </button>
                        </form>

                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-users-text">No one online in this view yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="info-tab-panel<?= $infoDefaultTab === 'requests' ? ' is-active' : ''; ?>" data-info-panel="requests">
        <div class="dm-requests-panel">
            <h3>DM requests</h3>
            <div class="dm-requests-list" id="dmRequestsList">
                <?php if (!empty($dmInboxRequests) && is_array($dmInboxRequests)): ?>
                    <?php foreach ($dmInboxRequests as $dmRequest): ?>
                        <article class="dm-request-item">
                            <div>
                                <strong><?= htmlspecialchars((string)($dmRequest['requester_name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars((string)($dmRequest['requester_class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> · wants to chat</span>
                            </div>
                            <div class="dm-request-actions">
                                <form method="POST" action="<?= htmlspecialchars(url('/users/dm-request/respond'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="request_id" value="<?= (int)($dmRequest['id'] ?? 0); ?>">
                                    <input type="hidden" name="decision" value="accepted">
                                    <button type="submit">Accept</button>
                                </form>
                                <form method="POST" action="<?= htmlspecialchars(url('/users/dm-request/respond'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="request_id" value="<?= (int)($dmRequest['id'] ?? 0); ?>">
                                    <input type="hidden" name="decision" value="declined">
                                    <button class="decline" type="submit">Decline</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($dmSentRequests) && is_array($dmSentRequests)): ?>
                    <?php foreach ($dmSentRequests as $dmRequest): ?>
                        <article class="dm-request-item is-sent">
                            <div>
                                <strong><?= htmlspecialchars((string)($dmRequest['recipient_name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span>Waiting for response</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($dmInboxRequests) && empty($dmSentRequests)): ?>
                    <p class="dm-empty">No pending requests.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="info-tab-panel<?= $infoDefaultTab === 'account' ? ' is-active' : ''; ?>" data-info-panel="account">
        <div class="account-panel">
            <div class="account-card">
                <?php if ($avatarPath !== ''): ?>
                    <img class="account-avatar" src="<?= htmlspecialchars((string)$avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php else: ?>
                    <div class="account-avatar account-avatar-fallback"><?= htmlspecialchars($initials !== '' ? $initials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <div class="account-copy">
                    <strong><?= htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?= $isCurrentUserOnline ? 'Online' : 'Offline'; ?> · <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="account-actions">
                <a class="account-action" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-person-circle"></i><span>Profile</span></a>
                <a class="account-action" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-people"></i><span>People</span></a>

                <?php if (has_moderator_privileges()): ?>
                    <a class="account-action" href="<?= htmlspecialchars(url(is_admin() ? '/admin/auth' : '/admin/chats'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-shield-lock"></i><span><?= is_admin() ? 'Central Admin' : 'Class Rep'; ?></span></a>
                <?php endif; ?>
                <form method="POST" action="<?= htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <button class="account-action account-action-button" type="submit"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
                </form>
            </div>
        </div>
    </div>
</div>
