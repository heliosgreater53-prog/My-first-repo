<?php
$incomingDmRequests = $incomingDmRequests ?? [];
$viewerId = $viewerId ?? 0;
?>
<div class="directory-aside-card">
    <div class="directory-aside-header">
        <h3>Directory</h3>
        <p class="muted">Search, discover, and connect with users.</p>
    </div>

    <?php if (!empty($incomingDmRequests)): ?>
        <div class="directory-aside-section">
            <div class="directory-aside-title">
                <strong>Pending requests</strong>
                <span class="request-badge"><?= count($incomingDmRequests); ?></span>
            </div>
            <div class="directory-request-list">
                <?php foreach ($incomingDmRequests as $dmReq): ?>
                    <?php
                    $requesterName = (string) ($dmReq['requester_name'] ?? 'User');
                    $requesterInitials = '';
                    foreach (array_slice(preg_split('/\s+/', trim($requesterName)) ?: [], 0, 2) as $part) {
                        $requesterInitials .= strtoupper(substr($part, 0, 1));
                    }
                    $requesterAvatar = (string) ($dmReq['requester_avatar'] ?? '');
                    $requestId = (int) ($dmReq['id'] ?? 0);
                    ?>
                    <div class="directory-request-item">
                        <div class="directory-request-avatar">
                            <?php if (!empty($requesterAvatar)): ?>
                                <img src="<?= htmlspecialchars($requesterAvatar, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                            <?php else: ?>
                                <div class="avatar-fallback"><?= htmlspecialchars($requesterInitials !== '' ? $requesterInitials : 'U', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="directory-request-info">
                            <strong><?= htmlspecialchars($requesterName, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p class="muted"><?= htmlspecialchars((string) ($dmReq['requester_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="directory-request-actions">
                            <form method="POST" action="<?= htmlspecialchars(url('/users/dm-request/respond'), ENT_QUOTES, 'UTF-8'); ?>" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="request_id" value="<?= $requestId; ?>">
                                <input type="hidden" name="decision" value="accepted">
                                <button class="directory-btn-accept" type="submit" title="Accept"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <form method="POST" action="<?= htmlspecialchars(url('/users/dm-request/respond'), ENT_QUOTES, 'UTF-8'); ?>" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="request_id" value="<?= $requestId; ?>">
                                <input type="hidden" name="decision" value="declined">
                                <button class="directory-btn-decline" type="submit" title="Decline"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="directory-aside-section">
            <div class="empty-section-notice">
                <i class="bi bi-inbox"></i>
                <p>No pending requests</p>
                <span class="muted">You'll see incoming DM requests here.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="directory-aside-section">
        <h4>Quick tips</h4>
        <ul class="directory-tips">
            <li>Search by name or email to find users</li>
            <li>Filter by class to narrow results</li>
            <li>Send a DM request to start chatting</li>
        </ul>
    </div>
</div>
