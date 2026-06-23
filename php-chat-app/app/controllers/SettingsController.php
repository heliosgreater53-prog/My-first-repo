<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Framework\Core\Controller;
use Framework\Core\Request;

class SettingsController extends Controller
{
    private function resolveThemePreference(string $themePref): string
    {
        $allowed = [
            // existing
            'system',
            'light',
            'dark',
            // explicit defaults
            'default_light',
            'default_dark',
            // named themes
            'midnight_blue',
            'forest_green',
            'sunset_orange',
            'lavender_purple',
            'rose_pink',
            'cyber_neon',
        ];

        return in_array($themePref, $allowed, true) ? $themePref : 'system';
    }

    public function index(Request $request): void
    {
        $user = auth_user();
        if ($user === null) {
            $this->redirect(url('/login'));
            return;
        }

        $userModel = new User();
        $userModel->migrate();
        $freshUser = $userModel->find((int) $user['id']) ?? $user;

        $this->view('profile.settings', [
            'title' => 'Settings | LivingSpring',
            'user' => $freshUser,
            'errors' => session()->getFlash('errors', []),
            'success' => session()->getFlash('success'),
            'themePref' => $freshUser['theme_preference'] ?? 'system',
            'reduceMotion' => (bool) ($freshUser['reduce_motion'] ?? false),
            'notificationsEnabled' => (bool) ((int) ($freshUser['notifications_enabled'] ?? 1)),
            'browserNotificationsEnabled' => (bool) ((int) ($freshUser['browser_notifications_enabled'] ?? 1)),
            'mentionNotificationsEnabled' => (bool) ((int) ($freshUser['mention_notifications_enabled'] ?? 1)),
            'dmNotificationsEnabled' => (bool) ((int) ($freshUser['dm_notifications_enabled'] ?? 1)),
            'compactUi' => (bool) ((int) ($freshUser['compact_ui'] ?? 0)),
        ]);
    }

    public function update(Request $request): void
    {
        $viewer = auth_user();
        if ($viewer === null) {
            $this->redirect(url('/login'));
            return;
        }

        $themePref = $this->resolveThemePreference(trim((string) $request->input('theme_pref', 'system')));

        $notificationsEnabled = ((string) $request->input('notifications_enabled', '0')) === '1';
        $updates = [
            'theme_preference' => $themePref,
            'reduce_motion' => ((string) $request->input('reduce_motion', '0')) === '1' ? 1 : 0,
            'notifications_enabled' => $notificationsEnabled ? 1 : 0,
            'browser_notifications_enabled' => $notificationsEnabled && ((string) $request->input('browser_notifications_enabled', '0')) === '1' ? 1 : 0,
            'mention_notifications_enabled' => $notificationsEnabled && ((string) $request->input('mention_notifications_enabled', '0')) === '1' ? 1 : 0,
            'dm_notifications_enabled' => $notificationsEnabled && ((string) $request->input('dm_notifications_enabled', '0')) === '1' ? 1 : 0,
            'compact_ui' => ((string) $request->input('compact_ui', '0')) === '1' ? 1 : 0,
        ];

        $userModel = new User();
        $userModel->migrate();

        try {
            $updatedUser = $userModel->updatePreferences((int) $viewer['id'], $updates);
            if (is_array($updatedUser)) {
                session()->put('user', $updatedUser);
            }
            session()->flash('success', 'Settings saved.');
            session()->flash('toast_type', 'success');
        } catch (\Throwable $e) {
            session()->flash('errors', ['database' => 'Unable to save settings right now.']);
            session()->flash('toast_type', 'error');
        }

        $this->redirect(url('/settings'));
    }

    public function themes(Request $request): void
    {
        $user = auth_user();
        if ($user === null) {
            $this->redirect(url('/login'));
            return;
        }

        $userModel = new User();
        $userModel->migrate();
        $freshUser = $userModel->find((int) $user['id']) ?? $user;

        $this->view('profile.themes', [
            'title' => 'Themes | LivingSpring',
            'user' => $freshUser,
            'errors' => session()->getFlash('errors', []),
            'success' => session()->getFlash('success'),
            'themePref' => $freshUser['theme_preference'] ?? 'system',
            'reduceMotion' => (bool) ($freshUser['reduce_motion'] ?? false),
        ]);
    }

    public function saveTheme(Request $request): void
    {
        $viewer = auth_user();
        if ($viewer === null) {
            $this->redirect(url('/login'));
            return;
        }

        $themeId = $this->resolveThemePreference(trim((string) $request->input('theme_id', 'system')));

        $userModel = new User();
        $userModel->migrate();

        $isAjax = ((string) ($request->header('X-Requested-With') ?? '')) === 'XMLHttpRequest';

        try {
            $updatedUser = $userModel->updatePreferences((int) $viewer['id'], [
                'theme_preference' => $themeId,
                'reduce_motion' => (int) ($viewer['reduce_motion'] ?? 0),
                'notifications_enabled' => (int) ($viewer['notifications_enabled'] ?? 1),
                'browser_notifications_enabled' => (int) ($viewer['browser_notifications_enabled'] ?? 1),
                'mention_notifications_enabled' => (int) ($viewer['mention_notifications_enabled'] ?? 1),
                'dm_notifications_enabled' => (int) ($viewer['dm_notifications_enabled'] ?? 1),
                'compact_ui' => (int) ($viewer['compact_ui'] ?? 0),
            ]);

            if (is_array($updatedUser)) {
                session()->put('user', $updatedUser);
            }

            session()->flash('success', 'Theme updated.');
            session()->flash('toast_type', 'success');

            if ($isAjax) {
                http_response_code(200);
                \header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'themeId' => $themeId]);
                exit;
            }
        } catch (\Throwable $e) {
            session()->flash('errors', ['database' => 'Unable to save theme right now.']);
            session()->flash('toast_type', 'error');

            if ($isAjax) {
                http_response_code(500);
                \header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Unable to save theme right now.']);
                exit;
            }
        }

        $this->redirect(url('/settings/themes'));
    }
}

