<?php
declare(strict_types=1);

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('view_path')) {
    function view_path(string $view): string
    {
        return base_path('app/views/' . str_replace('.', '/', $view) . '.php');
    }
}

if (!function_exists('config')) {
    function config(string $file): array
    {
        $path = base_path('config/' . $file . '.php');

        return file_exists($path) ? require $path : [];
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

        if ($scriptDir !== '' && basename($scriptDir) !== 'public') {
            $scriptDir .= '/public';
        }

        return $scriptDir . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path): string
    {
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

        if ($scriptDir !== '' && basename($scriptDir) !== 'public') {
            $scriptDir .= '/public';
        }

        return $scriptDir . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

        if ($scriptDir !== '' && basename($scriptDir) !== 'public') {
            $scriptDir .= '/public';
        }

        if ($path === '' || $path === '/') {
            return $scriptDir === '' ? '/' : $scriptDir . '/';
        }

        return $scriptDir . '/' . ltrim($path, '/');
    }
}

if (!function_exists('session')) {
    function session(): \Framework\Core\Session
    {
        return new \Framework\Core\Session();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $oldInput = session()->get('old', []);

        return $oldInput[$key] ?? $default;
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        $user = session()->get('user');

        return is_array($user) ? $user : null;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        $token = session()->get('_csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            session()->put('_csrf_token', $token);
        }

        return $token;
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $user = auth_user();

        return is_array($user) && (($user['role'] ?? 'student') === 'admin');
    }
}

if (!function_exists('has_admin_privileges')) {
    function has_admin_privileges(): bool
    {
        return is_admin();
    }
}

if (!function_exists('is_class_rep')) {
    function is_class_rep(): bool
    {
        $user = auth_user();

        return is_array($user) && in_array((string) ($user['role'] ?? 'student'), ['class_rep', 'moderator'], true);
    }
}

if (!function_exists('has_moderator_privileges')) {
    function has_moderator_privileges(): bool
    {
        $user = auth_user();

        return is_array($user) && in_array((string) ($user['role'] ?? 'student'), ['admin', 'class_rep', 'moderator'], true);
    }
}

if (!function_exists('migrate_app_schema')) {
    function migrate_app_schema(): void
    {
        (new \App\Models\User())->migrate();
        (new \App\Models\Room())->migrate();
        (new \App\Models\Message())->migrate();
        (new \App\Models\Post())->migrate();
        (new \App\Models\DmRequest())->migrate();
        (new \App\Models\MessageReport())->migrate();
        (new \App\Models\PasswordReset())->migrate();
        (new \App\Models\AdminAuditLog())->migrate();
        (new \App\Models\AppSetting())->migrate();
        (new \App\Models\InviteCode())->migrate();
        (new \App\Models\UserBlock())->migrate();
        (new \App\Models\UserNotification())->migrate();
    }
}

if (!function_exists('flagged_terms')) {
    /** @return list<string> */
    function flagged_terms(): array
    {
        $settings = new \App\Models\AppSetting();
        $settings->migrate();

        return $settings->getFlaggedTerms();
    }
}

if (!function_exists('send_app_mail')) {
    function send_app_mail(string $to, string $subject, string $body): bool
    {
        $mail = config('mail');
        if (!($mail['enabled'] ?? false)) {
            return false;
        }

        $from = (string) ($mail['from_address'] ?? 'noreply@livingspring.local');
        $fromName = (string) ($mail['from_name'] ?? 'LivingSpring');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . $fromName . ' <' . $from . '>',
        ];

        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }
}

if (!function_exists('process_message_mentions')) {
    function process_message_mentions(string $body, int $messageId, int $fromUserId, string $roomSlug): void
    {
        if (!preg_match_all('/@([A-Za-z][A-Za-z0-9._\-\s]{1,60})/u', $body, $matches)) {
            return;
        }

        $names = array_unique(array_map('trim', $matches[1] ?? []));
        if ($names === []) {
            return;
        }

        $userModel = new \App\Models\User();
        $userModel->migrate();
        $notifyModel = new \App\Models\UserNotification();
        $notifyModel->migrate();
        $from = $userModel->find($fromUserId);
        $nameMap = $userModel->findIdsByNames($names);

        foreach ($nameMap as $name => $targetId) {
            if ($targetId === $fromUserId) {
                continue;
            }
            $targetUser = $userModel->find((int) $targetId);
            if (is_array($targetUser) && (
                (int) ($targetUser['notifications_enabled'] ?? 1) !== 1
                || (int) ($targetUser['mention_notifications_enabled'] ?? 1) !== 1
            )) {
                continue;
            }
            $notifyModel->create(
                $targetId,
                'mention',
                ($from['name'] ?? 'Someone') . ' mentioned you: ' . mb_substr($body, 0, 120),
                $messageId,
                $fromUserId,
                $roomSlug
            );
        }
    }
}

if (!function_exists('feed_compose_rooms')) {
    /** Rooms students can post into from the school feed. */
    function feed_compose_rooms(array $rooms, bool $isAdmin): array
    {
        $options = [];
        foreach ($rooms as $room) {
            $slug = (string) ($room['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            if ($slug === 'notice-board' && !$isAdmin) {
                continue;
            }
            if (!empty($room['password_hash']) && empty($room['is_member']) && !$isAdmin) {
                continue;
            }
            if (in_array($room['scope'] ?? 'public', ['public', 'class'], true)) {
                $options[] = $room;
            }
        }

        return $options;
    }
}

if (!function_exists('is_admin_verified')) {
    function is_admin_verified(): bool
    {
        if (!has_admin_privileges()) {
            return false;
        }

        $verifiedAt = (int) session()->get('admin_verified_at', 0);

        return $verifiedAt > 0 && (time() - $verifiedAt) < 900;
    }
}

if (!function_exists('is_admin_route')) {
    function is_admin_route(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return (bool) preg_match('#/admin(?:/|$)#', $path);
    }
}

if (!function_exists('app_shell_data')) {
    /** Shared layout data for feed, room, people, profile, and settings pages. */
    function app_shell_data(string $layoutMode = 'feed'): array
    {
        $user = auth_user();
        if ($user === null) {
            return [];
        }

        $roomModel = new \App\Models\Room();
        $dmRequestModel = new \App\Models\DmRequest();
        $userModel = new \App\Models\User();
        $isAdmin = has_admin_privileges();

        $roomModel->migrate();
        $dmRequestModel->migrate();
        $userModel->migrate();

        $activeRoom = [
            'name' => 'School Feed',
            'slug' => 'home',
            'id' => 0,
            'description' => '',
            'scope' => 'public',
        ];

        if ($layoutMode === 'room') {
            $activeRoom = ['name' => '', 'slug' => '', 'id' => 0];
        }

        return [
            'user' => $user,
            'rooms' => $roomModel->accessibleForUser($user, $isAdmin),
            'activeRoom' => $activeRoom,
            'layoutMode' => $layoutMode,
            'currentPath' => '',
            'onlineUsers' => $userModel->getOnlineInRoom(0),
            'dmInboxRequests' => $dmRequestModel->inboxForUser((int) $user['id']),
            'dmSentRequests' => $dmRequestModel->sentForUser((int) $user['id']),
            'showRoomList' => !in_array($layoutMode, ['people', 'profile', 'settings'], true),
            'showAside' => true,
            'showDrawerToggle' => in_array($layoutMode, ['feed', 'room', 'people', 'profile', 'settings'], true),
        ];
    }
}

if (!function_exists('format_message_body')) {
    /** Basic markdown for chat/feed bodies (escaped, then formatted). */
    function format_message_body(string $body): string
    {
        $escaped = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $escaped) ?? $escaped;

        return nl2br($escaped, false);
    }
}
