<?php
$activeAdminPage = 'dashboard';
require view_path('partials.header');
$stats = $stats ?? [];
$recentMessages = $recentMessages ?? [];
$rooms = $rooms ?? [];
$reports = $reports ?? [];
require view_path('partials.admin-layout-open');
?>
            <div class="admin-page-head">
                <div>
                    <span class="admin-kicker">Overview</span>
                    <h1>Dashboard</h1>
                    <p>Monitor accounts, bans, and the newest chat activity from one place.</p>
                </div>
                <a class="auth-button button-reset admin-primary-action" href="<?= htmlspecialchars(url('/admin/rooms'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-door-open"></i> Manage rooms</a>
            </div>

            <div class="admin-stat-grid">
                <article class="admin-stat-card">
                    <span>Total users</span>
                    <strong><?= (int) ($stats['total_users'] ?? 0); ?></strong>
                </article>
                <article class="admin-stat-card">
                    <span>Admins</span>
                    <strong><?= (int) ($stats['admins'] ?? 0); ?></strong>
                </article>
                <article class="admin-stat-card">
                    <span>Active</span>
                    <strong><?= (int) ($stats['unbanned'] ?? 0); ?></strong>
                </article>
                <article class="admin-stat-card danger">
                    <span>Banned</span>
                    <strong><?= (int) ($stats['banned'] ?? 0); ?></strong>
                </article>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Broadcast announcement</h2>
                        <p>Send a message to the Notice Board, public rooms, or a class room.</p>
                    </div>
                </div>
                <form class="admin-broadcast-form" method="POST" action="<?= htmlspecialchars(url('/admin/broadcast'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <div class="field">
                        <label>Audience</label>
                        <select name="target" class="field-select">
                            <option value="notice-board">Notice Board</option>
                            <option value="all-public">All public rooms</option>
                            <?php foreach (['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'] as $className): ?>
                                <option value="class:<?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?> class room</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field field-span-2">
                        <label>Message</label>
                        <textarea name="body" rows="3" placeholder="Write a clear announcement"></textarea>
                    </div>
                    <button class="auth-button button-reset" type="submit">Broadcast</button>
                </form>
            </section>

            <div class="admin-two-column">
                <section class="admin-panel">
                    <div class="admin-panel-head">
                        <div>
                            <h2>Recent chat activity</h2>
                            <p>Latest messages across rooms.</p>
                        </div>
                        <a class="admin-text-link" href="<?= htmlspecialchars(url('/admin/chats'), ENT_QUOTES, 'UTF-8'); ?>">Review all</a>
                    </div>
                    <div class="admin-message-list">
                        <?php foreach ($recentMessages as $message): ?>
                            <article class="admin-message-item">
                                <div>
                                    <strong><?= htmlspecialchars((string) ($message['name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?= htmlspecialchars((string) ($message['room_name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars(date('M j, g:i A', strtotime((string) ($message['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <p><?= htmlspecialchars((string) (!empty($message['deleted_at']) ? '[deleted]' : ($message['body'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($recentMessages === []): ?>
                            <p class="admin-empty">No messages yet.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel-head">
                        <div>
                            <h2>Rooms</h2>
                            <p>Conversation spaces in the app.</p>
                        </div>
                        <a class="admin-text-link" href="<?= htmlspecialchars(url('/admin/rooms'), ENT_QUOTES, 'UTF-8'); ?>">Manage all</a>
                    </div>
                    <div class="admin-room-list">
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            $hasPassword = !empty($room['password_hash']);
                            $scopeLabels = ['public' => 'Public', 'class' => 'Class', 'direct' => 'Direct'];
                            $scopeLabel = $scopeLabels[$room['scope'] ?? 'public'] ?? 'Public';
                            ?>
                            <a class="admin-room-row" href="<?= htmlspecialchars(url('/admin/chats?room_id=' . (int) $room['id']), ENT_QUOTES, 'UTF-8'); ?>">
                                <div>
                                    <strong><?= htmlspecialchars((string) $room['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?><?= $hasPassword ? ' · Locked' : ''; ?></span>
                                </div>
                                <b><?= (int) ($room['message_count'] ?? 0); ?></b>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Latest reports</h2>
                        <p>Messages reported by students.</p>
                    </div>
                    <a class="admin-text-link" href="<?= htmlspecialchars(url('/admin/chats'), ENT_QUOTES, 'UTF-8'); ?>">Open review</a>
                </div>
                <div class="admin-report-list">
                    <?php foreach ($reports as $report): ?>
                        <article class="admin-report-item">
                            <div>
                                <strong><?= htmlspecialchars((string) ($report['reporter_name'] ?? 'Student'), ENT_QUOTES, 'UTF-8'); ?> → <?= htmlspecialchars((string) ($report['author_name'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars((string) ($report['room_name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <p><?= htmlspecialchars((string) (!empty($report['deleted_at']) ? '[deleted]' : ($report['body'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($reports === []): ?>
                        <p class="admin-empty">No reports yet.</p>
                    <?php endif; ?>
                </div>
            </section>
<?php require view_path('partials.admin-layout-close'); ?>
<?php require view_path('partials.footer'); ?>
