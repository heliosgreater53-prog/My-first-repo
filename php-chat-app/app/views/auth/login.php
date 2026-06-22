<?php require view_path('partials.header'); ?>
<main class="auth-page">
    <section class="auth-card">
        <aside class="auth-side">
            <div class="auth-brand">
                <div class="auth-brand-mark">LS</div>
                <div class="auth-brand-text">
                    <strong>LivingSpring</strong>
                    <span>School feed & class chat</span>
                </div>
            </div>
            <h1>Welcome back</h1>
            <p>Pick up your conversations, class updates, and direct chats in one calm workspace.</p>
        </aside>

        <div class="auth-main">
            <h2>Sign in</h2>
            <p>Enter your details to continue.</p>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors['auth'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars((string) $errors['auth'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" placeholder="you@example.com" value="<?= htmlspecialchars((string) old('email'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Enter your password">
                    <?php if (!empty($errors['password'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="field remember-field">
                    <label class="remember-label">
                        <input id="remember" name="remember" type="checkbox" value="1" <?= old('remember') === '1' ? 'checked' : ''; ?>>
                        Remember me for 7 days
                    </label>
                </div>
                <div class="auth-actions">
                    <button class="auth-button button-reset" type="submit">Sign in</button>
                    <a class="auth-link" href="<?= htmlspecialchars(url('/signup'), ENT_QUOTES, 'UTF-8'); ?>">Create an account</a>
                    <a class="auth-link" href="<?= htmlspecialchars(url('/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>">Forgot password?</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
