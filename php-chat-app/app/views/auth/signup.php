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
            <h1>Create account</h1>
            <p>Join your class space, message people quickly, and keep things organized from day one.</p>
        </aside>

        <div class="auth-main">
            <h2>Create your account</h2>
            <p>Fill in the details below to get started.</p>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars((string) $errors['database'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="<?= htmlspecialchars(url('/signup'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" placeholder="Your full name" value="<?= htmlspecialchars((string) old('name'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (!empty($errors['name'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="signup-email">Email</label>
                    <input id="signup-email" name="email" type="email" placeholder="you@example.com" value="<?= htmlspecialchars((string) old('email'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="signup-class">Class</label>
                    <select id="signup-class" name="class_name" class="field-select">
                        <option value="">Select your class</option>
                        <?php foreach ($classOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= old('class_name') === $option ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['class_name'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['class_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <?php if (!empty($signupRequiresInvite)): ?>
                    <div class="field">
                        <label for="invite-code">Invite code</label>
                        <input id="invite-code" name="invite_code" type="text" placeholder="Enter your school invite code" value="<?= htmlspecialchars((string) old('invite_code'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($errors['invite_code'])): ?>
                            <small class="field-error"><?= htmlspecialchars((string) $errors['invite_code'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="field">
                    <label for="signup-password">Password</label>
                    <input id="signup-password" name="password" type="password" placeholder="Create a password">
                    <?php if (!empty($errors['password'])): ?>
                        <small class="field-error"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
                <div class="field remember-field">
                    <label class="remember-label">
                        <input id="remember" name="remember" type="checkbox" value="1" <?= old('remember') === '1' ? 'checked' : ''; ?>>
                        Keep me signed in for 7 days
                    </label>
                </div>
                <div class="auth-actions">
                    <button class="auth-button button-reset" type="submit">Create Account</button>
                    <a class="auth-link" href="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>">Already have an account?</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require view_path('partials.footer'); ?>
