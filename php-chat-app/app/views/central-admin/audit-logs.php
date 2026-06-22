<?php
$activeAdminPage = 'audit';
require view_path('partials.header');
$auditLogs = $auditLogs ?? [];
require view_path('partials.central-admin-layout-open');
?>
            <div class="admin-page-head">
                <div>
                    <span class="admin-kicker">Audit</span>
                    <h1>Audit logs</h1>
                    <p>Review recent central admin and moderation actions across the platform.</p>
                </div>
                <a class="auth-button button-reset admin-primary-action" href="<?= htmlspecialchars(url('/central-admin/audit-logs/export'), ENT_QUOTES, 'UTF-8'); ?>">Export CSV</a>
            </div>

            <section class="admin-panel">
                <div class="admin-audit-list">
                    <?php foreach ($auditLogs as $log): ?>
                        <article class="admin-audit-item">
                            <strong><?= htmlspecialchars((string) ($log['admin_name'] ?? 'Admin'), ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars(str_replace('_', ' ', (string) ($log['action'] ?? 'updated')), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?= htmlspecialchars((string) ($log['target_name'] ?? 'System'), ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars(date('M j, g:i A', strtotime((string) ($log['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($log['details'])): ?>
                                <p><?= htmlspecialchars((string) $log['details'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($auditLogs === []): ?>
                        <p class="admin-empty">No admin actions logged yet.</p>
                    <?php endif; ?>
                </div>
            </section>
<?php require view_path('partials.admin-layout-close'); ?>
<?php require view_path('partials.footer'); ?>
