<?php
$extra_styles = [asset('css/landing.css')];
$extra_scripts = [asset('js/landing.js')];
require view_path('partials.header');

// A/B variant: check query param for variant (default: "signup_first")
$variant = strtolower(trim((string) ($_GET['variant'] ?? 'signup_first')));
$variant = in_array($variant, ['signup_first', 'learn_first'], true) ? $variant : 'signup_first';
$isPrimarySignup = $variant === 'signup_first';
?>

<main class="landing-page">
    <header class="landing-hero">
        <div class="landing-container">
            <div class="landing-content">
                <div class="landing-brand">
                    <div class="landing-brand-mark" aria-label="LivingSpring logo">LS</div>
                    <div class="landing-brand-text">
                        <strong>LivingSpring</strong>
                        <span>School feed & class chat</span>
                    </div>
                </div>

                <h1 class="landing-title">School conversations that actually work</h1>
                <p class="landing-sub">Bring class updates, group chats, and announcements into a calm, moderated space built for Livingspring schools.</p>

                <div class="landing-ctas" role="group" aria-label="Call to action buttons">
                    <?php if ($isPrimarySignup): ?>
                        <a class="btn primary" href="<?= htmlspecialchars(url('/signup'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Create a new account">Create an account</a>
                        <a class="btn" href="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Sign in to existing account">Log in</a>
                    <?php else: ?>
                        <a class="btn" href="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Sign in to existing account">Log in</a>
                        <a class="btn primary" href="<?= htmlspecialchars(url('/signup'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Create a new account">Create an account</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="landing-visual">
                <img src="<?= htmlspecialchars(asset('images/landing-mockup.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="LivingSpring chat interface showing class feeds, messages, and user profile with teal accent colors" loading="lazy">
            </div>
        </div>
    </header>

    <section class="landing-features-list" aria-label="Key features">
        <div class="landing-container">
            <div class="feature">
                <h3>Class feeds</h3>
                <p>Share announcements and resources with your class or the whole school.</p>
            </div>
            <div class="feature">
                <h3>Private rooms</h3>
                <p>Create focused groups for clubs, projects, and teachers.</p>
            </div>
            <div class="feature">
                <h3>SuperAdmin controls</h3>
                <p>Moderation, invites, and audits to keep conversations safe.</p>
            </div>
        </div>
    </section>

    <footer class="landing-footer">&copy; <?= date('Y') ?> LivingSpring</footer>
</main>

<?php require view_path('partials.footer'); ?>