<?php
$activeAdminPage = 'settings';
require view_path('partials.header');
require view_path('partials.central-admin-layout-open');
?>
            <div class="admin-page-head">
                <div>
                    <span class="admin-kicker">Configuration</span>
                    <h1>Settings</h1>
                    <p>Flagged words, signup invites, and mail status for the platform.</p>
                </div>
            </div>

            <section class="admin-panel">
                <form method="POST" action="<?= htmlspecialchars(url('/central-admin/settings'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <div class="field field-span-2">
                        <label>Flagged words (comma or newline separated)</label>
                        <textarea name="flagged_terms" rows="4"><?= htmlspecialchars(implode("\n", $flaggedTerms ?? []), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="field">
                        <label>
                            <input type="checkbox" name="signup_requires_invite" value="1" <?= !empty($signupRequiresInvite) ? 'checked' : ''; ?>>
                            Require invite code for signup
                        </label>
                    </div>
                    <p class="admin-empty">Email: <?= !empty($mailEnabled) ? 'enabled (LETSCHAT_MAIL_ENABLED)' : 'disabled — reset links show on screen only'; ?></p>
                    <button class="auth-button button-reset" type="submit">Save settings</button>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div><h2>Invite codes</h2><p>Share codes with students when signup is restricted.</p></div>
                </div>
                <form class="admin-create-form" method="POST" action="<?= htmlspecialchars(url('/central-admin/invites/create'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrf_input(); ?>
                    <div class="field"><label>Code (optional)</label><input name="code" type="text" placeholder="Auto-generate if empty"></div>
                    <div class="field"><label>Class</label>
                        <select name="class_name" class="field-select"><option value="">Any</option>
                            <?php foreach ($classOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field"><label>Max uses</label><input name="max_uses" type="number" value="1" min="1"></div>
                    <div class="field"><label>Expires</label><input name="expires_at" type="datetime-local"></div>
                    <button class="auth-button button-reset" type="submit">Create invite</button>
                </form>
                <div class="admin-list" style="margin-top:16px;">
                    <?php foreach ($invites as $invite): ?>
                        <article class="admin-card">
                            <strong><?= htmlspecialchars((string) $invite['code'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?= (int) ($invite['uses_count'] ?? 0); ?> / <?= (int) ($invite['max_uses'] ?? 1); ?> uses</span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
<?php require view_path('partials.admin-layout-close'); ?>
<?php require view_path('partials.footer'); ?>
