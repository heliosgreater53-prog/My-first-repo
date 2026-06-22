<?php require view_path('partials.header'); ?>
<main class="auth-page">
    <section class="auth-card">
        <aside class="auth-side">
            <div class="auth-brand">
                <div class="auth-brand-mark">LS</div>
                <div class="auth-brand-text">
                    <strong>LivingSpring</strong>
                    <span>Choose a new password</span>
                </div>
            </div>
            <h1>Set a new password</h1>
            <p>Choose a secure password so you can return to your chats without any friction.</p>
        </aside>

        <div class="auth-main">
            <h2>Reset password</h2>
            <p>Complete the form below.</p>

            <?php if (!empty($errors['token'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars((string) $errors['token'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="<?= htmlspecialchars(url('/reset-password'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars((string) $token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="reset-password">New password</label>
                    <input id="reset-password" name="password" type="password" placeholder="Minimum 6 characters">
                    <?php if (!empty($errors['password'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="reset-password-confirmation">Confirm password</label>
                    <input id="reset-password-confirmation" name="password_confirmation" type="password" placeholder="Repeat the password">
                    <?php if (!empty($errors['password_confirmation'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['password_confirmation'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="auth-actions">
                    <button class="auth-button button-reset" type="submit">Reset password</button>
                    <a class="auth-link" href="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">Back to login</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
