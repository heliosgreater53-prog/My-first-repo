<?php
$pageTitle = $title ?? 'LetsChat';
$stylesheetPath = base_path('public/assets/css/styles.css');
$stylesheetVersion = file_exists($stylesheetPath) ? (string) filemtime($stylesheetPath) : (string) time();
$hybridStylesheetPath = base_path('public/assets/css/hybrid.css');
$hybridStylesheetVersion = file_exists($hybridStylesheetPath) ? (string) filemtime($hybridStylesheetPath) : (string) time();
$isAdminRoute = is_admin_route();
$adminStylesheetPath = base_path('public/assets/css/admin.css');
$adminStylesheetVersion = file_exists($adminStylesheetPath) ? (string) filemtime($adminStylesheetPath) : (string) time();
$currentUser = auth_user();
$themePreference = (string) ($currentUser['theme_preference'] ?? 'system');
$reduceMotion = (bool) ((int) ($currentUser['reduce_motion'] ?? 0));
$notificationsEnabled = (bool) ((int) ($currentUser['notifications_enabled'] ?? 1));
$browserNotificationsEnabled = (bool) ((int) ($currentUser['browser_notifications_enabled'] ?? 1));
$dmNotificationsEnabled = (bool) ((int) ($currentUser['dm_notifications_enabled'] ?? 1));
$compactUi = (bool) ((int) ($currentUser['compact_ui'] ?? 0));
$bodyClasses = [];
if ($isAdminRoute) {
    $bodyClasses[] = 'admin-route';
}
if ($themePreference === 'dark') {
    $bodyClasses[] = 'theme-dark';
}
if ($reduceMotion) {
    $bodyClasses[] = 'reduce-motion';
}
if ($compactUi) {
    $bodyClasses[] = 'compact-ui';
}
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($isAdminRoute && preg_match('#/admin/auth(?:/|$)#', $requestPath)) {
    $bodyClasses[] = 'admin-auth-page';
}
$bodyClassAttr = $bodyClasses !== [] ? ' class="' . htmlspecialchars(implode(' ', $bodyClasses), ENT_QUOTES, 'UTF-8') . '"' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset('images/favicon.svg'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/styles.css') . '?v=' . $stylesheetVersion, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/hybrid.css') . '?v=' . $hybridStylesheetVersion, ENT_QUOTES, 'UTF-8'); ?>">

    <?php
    $modernStylesheetPath = base_path('public/assets/css/modern.css');
    $modernStylesheetVersion = file_exists($modernStylesheetPath) ? (string) filemtime($modernStylesheetPath) : (string) time();
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/modern.css') . '?v=' . $modernStylesheetVersion, ENT_QUOTES, 'UTF-8'); ?>">

    <?php if ($isAdminRoute): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/admin.css') . '?v=' . $adminStylesheetVersion, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if (!empty($extra_styles) && is_array($extra_styles)): ?>
        <?php foreach ($extra_styles as $es): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($es, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
    <?php elseif (!empty($extra_styles) && is_string($extra_styles)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($extra_styles, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
</head>

<body<?= $bodyClassAttr; ?>
    data-auth-user-id="<?= htmlspecialchars((string) (($currentUser['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
    data-csrf-token="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
    data-is-admin="<?= has_admin_privileges() ? '1' : '0'; ?>"
    data-theme-pref="<?= htmlspecialchars($themePreference, ENT_QUOTES, 'UTF-8'); ?>"
    data-notifications-enabled="<?= $notificationsEnabled ? '1' : '0'; ?>"
    data-browser-notifications-enabled="<?= ($notificationsEnabled && $browserNotificationsEnabled) ? '1' : '0'; ?>"
    data-dm-notifications-enabled="<?= ($notificationsEnabled && $dmNotificationsEnabled) ? '1' : '0'; ?>">
    <script>
        (function() {
            const authUserId = Number(document.body.dataset.authUserId || 0);
            if (authUserId === 0) {
                console.log('[Notif] No user logged in, skipping');
                return;
            }
            if (document.body.dataset.browserNotificationsEnabled !== '1') {
                console.log('[Notif] Browser notifications disabled by settings');
                return;
            }
            console.log('[Notif] Initializing for user', authUserId);
            if (!('serviceWorker' in navigator) || !('Notification' in window)) {
                console.warn('[Notif] ServiceWorker/Notification not supported');
                return;
            }
            const swPath = new URL('sw.js', location.href).href;
            console.log('[Notif] Registering SW at', swPath);
            navigator.serviceWorker.register(swPath).then(r => {
                console.log('[Notif] SW registered', {
                    scope: r.scope,
                    active: !!r.active
                });
                if (!navigator.serviceWorker.controller) {
                    window.addEventListener('load', () => {
                        setTimeout(() => {
                            if (!navigator.serviceWorker.controller) {
                                console.log('[Notif] Service worker will control this page after the next normal navigation');
                            } else {
                                console.log('[Notif] Controller active');
                            }
                        }, 300);
                    });
                }
            }).catch(e => console.error('[Notif] SW registration error:', e));

            let asked = false;
            const askPerm = () => {
                if (asked) return;
                asked = true;
                console.log('[Notif] User interaction');
                if (Notification.permission === 'default') {
                    console.log('[Notif] Requesting permission...');
                    Notification.requestPermission().then(p => {
                        console.log('[Notif] Permission result:', p);
                        if (p === 'granted') {
                            console.log('[Notif] Granted');
                        }
                    }).catch(e => console.error('[Notif] Permission error:', e));
                } else {
                    console.log('[Notif] Permission already:', Notification.permission);
                }
            };
            document.addEventListener('click', askPerm, {
                once: true,
                capture: true
            });
            document.addEventListener('keydown', askPerm, {
                once: true,
                capture: true
            });
        })();
    </script>
    <script>
        (() => {
            const pref = document.body.dataset.themePref || 'system';
            const stored = localStorage.getItem('letschat-theme');
            const wantsDark = pref === 'dark' || (pref === 'system' && stored === 'dark');
            if (wantsDark) {
                document.body.classList.add('theme-dark');
            } else if (pref === 'light') {
                document.body.classList.remove('theme-dark');
            }
        })();
    </script>
    <button class="theme-toggle" type="button" id="themeToggle" title="Toggle dark mode"><i class="bi bi-moon-stars"></i></button>
    <div class="pro-tip-notification" id="proTipNotification" role="status" aria-live="polite">
        Pro tip: Fullscreen makes everything nicer
    </div>
    <?php
    $toastMessage = session()->getFlash('toast');
    $toastType = session()->getFlash('toast_type', 'success');
    ?>
    <?php if (!empty($toastMessage)): ?>
        <div class="toast-stack">
            <div class="toast toast-<?= htmlspecialchars((string) $toastType, ENT_QUOTES, 'UTF-8'); ?>">
                <?= htmlspecialchars((string) $toastMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if (auth_user() !== null): ?>
        <script>
            window.setInterval(() => {
                fetch('<?= htmlspecialchars(url('/api/presence/ping'), ENT_QUOTES, 'UTF-8'); ?>', {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(() => {});
            }, 60000);
        </script>
    <?php endif; ?>
    <script>
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            document.body.classList.toggle('theme-dark');
            localStorage.setItem('letschat-theme', document.body.classList.contains('theme-dark') ? 'dark' : 'light');
        });
    </script>

    <!-- Generic Modal Overlay -->
    <div class="modal-overlay" id="modalOverlay" style="display:none;">
        <div class="modal-box" id="modalBox">
            <div class="modal-header" id="modalHeader">
                <h3 id="modalTitle"></h3>
                <button class="modal-close" id="modalClose" type="button" title="Close">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer" id="modalFooter"></div>
        </div>
    </div>

    <script>
        (function() {
            const overlay = document.getElementById('modalOverlay');
            const box = document.getElementById('modalBox');
            const title = document.getElementById('modalTitle');
            const body = document.getElementById('modalBody');
            const footer = document.getElementById('modalFooter');
            const closeBtn = document.getElementById('modalClose');

            window.openModal = function(opts) {
                title.textContent = opts.title || '';
                body.innerHTML = opts.body || '';
                footer.innerHTML = opts.footer || '';
                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            };

            window.closeModal = function() {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            };

            closeBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeModal();
            });

            window.showConfirm = function(message, onConfirm) {
                openModal({
                    title: 'Confirm',
                    body: '<p id="confirmMessage"></p>',
                    footer: '<button class="button-reset auth-button" id="confirmCancelButton" type="button">Cancel</button>' +
                        '<button class="button-reset auth-button" id="confirmDeleteButton" type="button" style="background:linear-gradient(135deg,#dc2626,#f97316);">Delete</button>'
                });
                setTimeout(function() {
                    var confirmMessage = document.getElementById('confirmMessage');
                    var cancelButton = document.getElementById('confirmCancelButton');
                    var deleteButton = document.getElementById('confirmDeleteButton');
                    if (confirmMessage) confirmMessage.textContent = message;
                    if (cancelButton) cancelButton.addEventListener('click', closeModal);
                    if (deleteButton) {
                        deleteButton.addEventListener('click', function() {
                            closeModal();
                            if (typeof onConfirm === 'function') onConfirm();
                        });
                    }
                }, 0);
            };

            window.showPasswordPrompt = function(onSubmit) {
                openModal({
                    title: 'Enter room password',
                    body: '<input type="password" id="roomPasswordInput" class="modal-input" placeholder="Room password…" style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#fff;color:var(--text);">',
                    footer: '<button class="button-reset auth-button" onclick="closeModal();">Cancel</button>' +
                        '<button class="button-reset auth-button" id="roomPasswordSubmit">Join</button>'
                });
                setTimeout(function() {
                    var inp = document.getElementById('roomPasswordInput');
                    if (inp) inp.focus();
                    var btn = document.getElementById('roomPasswordSubmit');
                    if (btn) btn.addEventListener('click', function() {
                        onSubmit(inp.value);
                    });
                }, 50);
            };
        })();
    </script>

    <script>
        (function() {
            const proTip = document.getElementById('proTipNotification');
            if (!proTip) return;
            const MIN_INTERVAL = 20 * 60 * 1000; // 20 minutes
            const shownKey = 'proTipLastShown';

            function canShow() {
                const last = localStorage.getItem(shownKey);
                if (!last) return true;
                return Date.now() - parseInt(last, 10) > MIN_INTERVAL;
            }

            function showTip() {
                proTip.classList.add('show');
                localStorage.setItem(shownKey, String(Date.now()));
                setTimeout(() => proTip.classList.remove('show'), 3000);
            }

            if (canShow()) showTip();
            setInterval(() => {
                if (canShow()) showTip();
            }, MIN_INTERVAL);
        })();
    </script>

</body>

</html>