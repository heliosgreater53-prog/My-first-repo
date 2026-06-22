<?php require view_path('partials.header'); ?>
<main class="auth-page">
    <section class="auth-card">
        <aside class="auth-side">
            <div class="auth-brand">
                <div class="auth-brand-mark">LS</div>
                <div class="auth-brand-text">
                    <strong>LivingSpring</strong>
                    <span>Admin access</span>
                </div>
            </div>
            <h1>Verify your identity</h1>
            <p>Class reps and other privileged accounts confirm their password before opening the admin panel.</p>
        </aside>

        <div class="auth-main">
            <h2>Confirm admin access</h2>
            <p>Re-enter your password to continue.</p>

            <?php if (!empty($errors['password'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="<?= htmlspecialchars(url('/admin/auth'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <div class="field">
                    <label for="admin-password">Password</label>
                    <input id="admin-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                </div>
                <div class="auth-actions">
                    <button class="auth-button button-reset" type="submit">Continue</button>
                    <a class="auth-link" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">Back to feed</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
