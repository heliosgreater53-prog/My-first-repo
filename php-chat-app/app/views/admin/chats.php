<?php
$activeAdminPage = 'chats';
require view_path('partials.header');
$flaggedTerms = $flaggedTerms ?? [];
$reports = $reports ?? [];
$isCentralAdmin = (bool) ($isCentralAdmin ?? is_admin());
require view_path('partials.admin-layout-open');
?>
            <div class="admin-page-head">
                <div>
                    <span class="admin-kicker">Moderation</span>
                    <h1>Chat review</h1>
                    <p><?= $isCentralAdmin ? 'Read room activity, review reports, delete harmful messages, and ban accounts when needed.' : 'Review your class activity, handle reports, delete harmful messages, and temporarily mute students.'; ?></p>
                    <?php if (($openReportCount ?? 0) > 0): ?>
                        <p><span class="unread-badge"><?= (int) $openReportCount; ?> open reports</span></p>
                    <?php endif; ?>
                </div>
            </div>

            <section class="admin-panel">
                <form class="admin-filter-bar" method="GET" action="<?= htmlspecialchars(url('/admin/chats'), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field">
                        <label>Report status</label>
                        <select name="report_status" class="field-select">
                            <option value="">All</option>
                            <option value="open" <?= ($reportStatus ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="resolved" <?= ($reportStatus ?? '') === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="dismissed" <?= ($reportStatus ?? '') === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Room</label>
                        <select name="room_id" class="field-select">
                            <option value="0">All rooms</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= htmlspecialchars((string) $room['id'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) $selectedRoomId === (int) $room['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $room['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="auth-button button-reset" type="submit">Filter</button>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Student reports</h2>
                        <p>Messages reported from the chat screen.</p>
                    </div>
                </div>
                <div class="admin-report-list">
                    <?php foreach ($reports as $report): ?>
                        <article class="admin-report-item <?= (int) ($report['author_is_active'] ?? 1) !== 1 ? 'is-banned' : ''; ?>">
                            <div>
                                <strong><?= htmlspecialchars((string) ($report['reporter_name'] ?? 'Student'), ENT_QUOTES, 'UTF-8'); ?> reported <?= htmlspecialchars((string) ($report['author_name'] ?? 'a user'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars((string) ($report['room_name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) ucfirst((string) ($report['status'] ?? 'open')), ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars(date('M j, g:i A', strtotime((string) ($report['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <p><?= htmlspecialchars((string) (!empty($report['deleted_at']) ? '[deleted]' : ($report['body'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="admin-chat-action-row">
                                <?php foreach (['open' => 'Reopen', 'resolved' => 'Resolve', 'dismissed' => 'Dismiss'] as $status => $label): ?>
                                    <form method="POST" action="<?= htmlspecialchars(url('/admin/reports/update'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="report_id" value="<?= htmlspecialchars((string) ($report['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="admin-moderation-button <?= $status === 'resolved' ? 'success' : ''; ?>" type="submit"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($reports === []): ?>
                        <p class="admin-empty">No student reports yet.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Recent messages</h2>
                        <p>Newest messages appear first.</p>
                    </div>
                </div>

                <div class="admin-chat-review-list">
                    <?php foreach ($messages as $message): ?>
                        <?php
                        $isBanned = (int) ($message['is_active'] ?? 1) !== 1;
                        $isMuted = !empty($message['is_muted']);
                        $canMuteAuthor = $isCentralAdmin
                            ? ($message['role'] ?? 'student') !== 'admin'
                            : ($message['role'] ?? 'student') === 'student';
                        $messageBody = (string) ($message['body'] ?? '');
                        $matchedTerms = [];
                        foreach ($flaggedTerms as $term) {
                            if ($term !== '' && stripos($messageBody, (string) $term) !== false) {
                                $matchedTerms[] = (string) $term;
                            }
                        }
                        ?>
                        <article class="admin-chat-review-item <?= $isBanned ? 'is-banned' : ''; ?><?= $matchedTerms !== [] ? ' is-flagged' : ''; ?>">
                            <div class="admin-chat-meta">
                                <div>
                                    <strong><?= htmlspecialchars((string) ($message['name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?= htmlspecialchars((string) ($message['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="admin-chat-tags">
                                    <span><?= htmlspecialchars((string) ($message['room_name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span><?= htmlspecialchars((string) ($message['class_name'] ?? 'Class'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span><?= $isBanned ? 'Banned' : 'Unbanned'; ?></span>
                                    <?php if ($isMuted): ?>
                                        <span class="admin-risk-tag">Muted</span>
                                    <?php endif; ?>
                                    <?php if ($matchedTerms !== []): ?>
                                        <span class="admin-risk-tag">Flagged</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($matchedTerms !== []): ?>
                                <div class="admin-flagged-terms">
                                    <?php foreach ($matchedTerms as $term): ?>
                                        <span><?= htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p><?= nl2br(htmlspecialchars((string) (!empty($message['deleted_at']) ? '[deleted]' : ($message['body'] ?? '')), ENT_QUOTES, 'UTF-8')); ?></p>
                            <div class="admin-chat-actions">
                                <span><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($message['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span>
                                <div class="admin-chat-action-row">
                                    <?php if (empty($message['deleted_at'])): ?>
                                        <form method="POST" action="<?= htmlspecialchars(url('/admin/messages/delete'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= csrf_input(); ?>
                                            <input type="hidden" name="message_id" value="<?= htmlspecialchars((string) ($message['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/admin/chats?room_id=' . (int) $selectedRoomId), ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="admin-moderation-button danger" type="submit">Delete message</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canMuteAuthor): ?>
                                        <?php if ($isMuted): ?>
                                            <form method="POST" action="<?= htmlspecialchars(url('/admin/users/unmute'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= csrf_input(); ?>
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($message['user_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/admin/chats?room_id=' . (int) $selectedRoomId), ENT_QUOTES, 'UTF-8'); ?>">
                                                <button class="admin-toggle-button small unban" type="submit"><span></span>Unmute</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= htmlspecialchars(url('/admin/users/mute'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= csrf_input(); ?>
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($message['user_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="duration" value="24h">
                                                <input type="hidden" name="reason" value="Muted from chat review">
                                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/admin/chats?room_id=' . (int) $selectedRoomId), ENT_QUOTES, 'UTF-8'); ?>">
                                                <button class="admin-toggle-button small ban" type="submit"><span></span>Mute 24h</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($isCentralAdmin): ?>
                                    <form method="POST" action="<?= htmlspecialchars(url('/admin/users/ban-toggle'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($message['user_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="is_active" value="<?= $isBanned ? '1' : '0'; ?>">
                                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(url('/admin/chats?room_id=' . (int) $selectedRoomId), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="admin-toggle-button small <?= $isBanned ? 'unban' : 'ban'; ?>" type="submit">
                                            <span></span>
                                            <?= $isBanned ? 'Unban' : 'Ban'; ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($messages === []): ?>
                        <p class="admin-empty">No messages found for this room.</p>
                    <?php endif; ?>
                </div>
            </section>
<?php require view_path('partials.admin-layout-close'); ?>
<?php require view_path('partials.footer'); ?>
