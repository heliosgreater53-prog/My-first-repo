<?php require view_path('partials.header'); ?>
<main class="auth-page">
    <section class="auth-card">
        <aside class="auth-side">
            <div class="auth-brand">
                <div class="auth-brand-mark">LS</div>
                <div class="auth-brand-text">
                    <strong>LivingSpring</strong>
                    <span>Password recovery</span>
                </div>
            </div>
            <h1>Reset access</h1>
            <p>Enter your email address and generate a fresh reset link for your account.</p>
        </aside>

        <div class="auth-main">
            <h2>Forgot password</h2>
            <p>Use your registered email to continue.</p>

            <?php if (!empty($resetLink)): ?>
                <div class="alert alert-success">Reset link: <a class="inline-link" href="<?= htmlspecialchars((string) $resetLink, ENT_QUOTES, 'UTF-8'); ?>">Open reset page</a></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="<?= htmlspecialchars(url('/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <div class="field">
                    <label for="forgot-email">Email</label>
                    <input id="forgot-email" name="email" type="email" placeholder="you@example.com">
                    <?php if (!empty($errors['email'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="auth-actions">
                    <button class="auth-button button-reset" type="submit">Generate reset link</button>
                    <a class="auth-link" href="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">Back to login</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
