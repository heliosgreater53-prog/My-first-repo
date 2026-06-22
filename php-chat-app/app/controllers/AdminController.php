<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminAuditLog;
use App\Models\AppSetting;
use App\Models\InviteCode;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\Room;
use App\Models\User;
use Framework\Core\Controller;
use Framework\Core\Request;

class AdminController extends Controller
{
    private const ADMIN_AUTH_FAILURE_LIMIT = 5;

    private array $classOptions = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];
    private array $statusOptions = ['Online', 'Offline'];
    private array $roleOptions = ['student', 'class_rep', 'admin'];

    private function requireAdmin(): void
    {
        $user = auth_user();
        if ($user === null || !has_admin_privileges()) {
            session()->flash('toast', 'Admin access required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }
    }

    public function redirectToAuth(Request $request): void
    {
        session()->forget('admin_verified_at');
        $this->redirect(url('/admin/auth'));
    }

    public function auth(Request $request): void
    {
        $this->view('admin.auth', [
            'title' => 'Admin Access | LetsChat',
            'errors' => session()->getFlash('errors', []),
        ]);
    }

    public function verify(Request $request): void
    {
        $user = auth_user();

        if ($user === null) {
            $this->redirect(url('/login'));
        }

        $password = (string) $request->input('password', '');

        if ($password === '') {
            session()->flash('errors', ['password' => 'Enter your password to continue.']);
            $this->redirect(url('/admin/auth'));
        }

        $userModel = new User();
        $userModel->migrate();
        $storedUser = $userModel->findByEmail((string) ($user['email'] ?? ''));

        if (
            $storedUser === null
            || !has_admin_privileges()
            || !password_verify($password, (string) ($storedUser['password'] ?? ''))
        ) {
            $this->registerFailedAdminAttempt();
            return;
        }

        session()->forget('admin_auth_attempts');
        session()->put('admin_verified_at', time());
        session()->flash('toast', 'Admin access confirmed.');
        session()->flash('toast_type', 'success');

        if (is_admin()) {
            $this->redirect(url('/central-admin/dashboard'));
        }

        $this->redirect(url('/admin/chats'));
    }

    public function index(Request $request): void
    {
        $this->requireCentralAdmin();

        [$userModel, $roomModel, $messageModel, $reportModel, $auditLogModel] = $this->bootAdminModels();

        $this->view('admin.central-dashboard', [
            'title' => 'Central Admin Dashboard | LetsChat',
            'activeAdminPage' => 'dashboard',
            'stats' => $userModel->adminStats(),
            'recentMessages' => $messageModel->recentForAdmin(5),
            'rooms' => $roomModel->allForAdmin(),
            'reports' => $reportModel->recent(5),
            'openReportCount' => $reportModel->countOpen(),
        ]);
    }

    public function users(Request $request): void
    {
        $this->requireModeratorPanel();

        [$userModel] = $this->bootAdminModels();
        $viewer = auth_user();
        $isCentralAdmin = is_admin();
        $term = trim((string) $request->input('q', ''));
        $className = trim((string) $request->input('class_name', ''));
        $banState = trim((string) $request->input('ban_state', ''));
        if (!in_array($className, $this->classOptions, true)) {
            $className = '';
        }
        if (!in_array($banState, ['banned', 'unbanned'], true)) {
            $banState = '';
        }
        if (!$isCentralAdmin) {
            $className = (string) ($viewer['class_name'] ?? '');
            $banState = '';
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 15;
        $total = $userModel->searchCount($term, $className, $banState);

        $this->view('admin.users', [
            'title' => 'User Management | LivingSpring',
            'activeAdminPage' => 'users',
            'users' => $userModel->searchPaginated($term, $className, $banState, $page, $perPage),
            'userPage' => $page,
            'userPages' => (int) max(1, ceil($total / $perPage)),
            'userTotal' => $total,
            'classOptions' => $this->classOptions,
            'statusOptions' => $this->statusOptions,
            'roleOptions' => $this->roleOptions,
            'isCentralAdmin' => $isCentralAdmin,
            'isClassRepPanel' => !$isCentralAdmin,
            'filters' => [
                'q' => $term,
                'class_name' => $className,
                'ban_state' => $banState,
            ],
        ]);
    }

    public function createUser(Request $request): void
    {
        $this->requireAuthorizedAdmin();

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $className = trim((string) $request->input('class_name', 'JSS1'));
        $role = trim((string) $request->input('role', 'student'));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Name is required.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'A valid email is required.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!in_array($className, $this->classOptions, true) || !in_array($role, $this->roleOptions, true)) {
            $errors[] = 'Choose a valid class and access level.';
        }

        $userModel = new User();
        $userModel->migrate();

        if ($email !== '' && $userModel->findByEmail($email) !== null) {
            $errors[] = 'That email is already registered.';
        }

        if ($errors !== []) {
            session()->flash('toast', implode(' ', $errors));
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $createdUser = $userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'class_name' => $className,
            'role' => $role,
            'status' => 'Offline',
            'room_name' => 'General Room',
            'is_active' => 1,
        ]);

        if ($createdUser === null) {
            session()->flash('toast', 'Unable to create that user.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        session()->flash('toast', 'Account created successfully.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/users'));
    }

    public function updateUser(Request $request): void
    {
        $this->requireAuthorizedAdmin();

        $userId = (int) $request->input('user_id', 0);
        $role = trim((string) $request->input('role', 'student'));
        $status = trim((string) $request->input('status', 'Online'));
        $className = trim((string) $request->input('class_name', 'JSS1'));
        $roomName = trim((string) $request->input('room_name', 'General Room'));
        $headline = trim((string) $request->input('headline', ''));
        $bio = trim((string) $request->input('bio', ''));
        $password = (string) $request->input('password', '');

        if (!in_array($role, $this->roleOptions, true) || !in_array($status, $this->statusOptions, true) || !in_array($className, $this->classOptions, true)) {
            session()->flash('toast', 'One or more admin values were invalid.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        if ($password !== '' && strlen($password) < 6) {
            session()->flash('toast', 'Temporary password must be at least 6 characters.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $userModel = new User();
        $userModel->migrate();
        $existingUser = $userModel->find($userId);
        if ($existingUser === null) {
            session()->flash('toast', 'Unable to update that user.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $updatedUser = $userModel->updateAdminFields($userId, [
            'role' => $role,
            'status' => $status,
            'class_name' => $className,
            'room_name' => $roomName !== '' ? $roomName : 'General Room',
            'headline' => $headline,
            'bio' => $bio,
            'password' => $password,
            'is_active' => (int) ($existingUser['is_active'] ?? 1),
        ]);

        if ($updatedUser === null) {
            session()->flash('toast', 'Unable to update that user.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $authUser = auth_user();
        if (is_array($authUser) && (int) $authUser['id'] === $userId) {
            session()->put('user', $updatedUser);
        }

        session()->flash('toast', 'User updated successfully.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/users'));
    }

    public function chats(Request $request): void
    {
        $this->requireModeratorPanel();

        [$userModel, $roomModel, $messageModel, $reportModel] = $this->bootAdminModels();
        $viewer = auth_user();
        $classScope = is_admin() ? '' : (string) ($viewer['class_name'] ?? '');
        $roomId = (int) $request->input('room_id', 0);
        $reportStatus = trim((string) $request->input('report_status', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 40;
        $offset = ($page - 1) * $perPage;

        $rooms = $roomModel->allForAdmin();
        if ($classScope !== '') {
            $rooms = array_values(array_filter($rooms, static fn(array $room): bool => ($room['scope'] ?? '') !== 'direct' && (($room['scope'] ?? '') !== 'class' || ($room['class_name'] ?? '') === $classScope)));
            if ($roomId > 0 && !in_array($roomId, array_map(static fn(array $room): int => (int) $room['id'], $rooms), true)) {
                $roomId = 0;
            }
        }

        $this->view('admin.chats', [
            'title' => 'Chat Moderation | LivingSpring',
            'activeAdminPage' => 'chats',
            'rooms' => $rooms,
            'messages' => $messageModel->recentForAdmin($perPage, $roomId, $offset, $classScope),
            'reports' => $reportModel->recent(50, $reportStatus, $classScope),
            'openReportCount' => $reportModel->countOpen($classScope),
            'users' => $userModel->search('', $classScope),
            'selectedRoomId' => $roomId,
            'reportStatus' => $reportStatus,
            'flaggedTerms' => flagged_terms(),
            'chatPage' => $page,
            'isCentralAdmin' => is_admin(),
            'classScope' => $classScope,
        ]);
    }

    public function broadcast(Request $request): void
    {
        $this->requireAuthorizedAdmin();

        [$userModel, $roomModel, $messageModel,, $auditLogModel] = $this->bootAdminModels();
        $user = auth_user();
        $target = trim((string) $request->input('target', 'notice-board'));
        $body = trim((string) $request->input('body', ''));

        if (!is_array($user) || $body === '') {
            session()->flash('toast', 'Write an announcement before broadcasting.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/dashboard'));
        }

        $rooms = [];
        if ($target === 'all-public') {
            $rooms = array_filter($roomModel->allForAdmin(), static fn(array $room): bool => ($room['scope'] ?? '') === 'public');
        } elseif (str_starts_with($target, 'class:')) {
            $className = substr($target, 6);
            $rooms = array_filter($roomModel->allForAdmin(), static fn(array $room): bool => ($room['scope'] ?? '') === 'class' && ($room['class_name'] ?? '') === $className);
        } else {
            $noticeRoom = $roomModel->findBySlug('notice-board');
            $rooms = $noticeRoom !== null ? [$noticeRoom] : [];
        }

        foreach ($rooms as $room) {
            $messageModel->create((int) $room['id'], (int) $user['id'], '[Announcement] ' . $body);
        }

        $auditLogModel->record((int) $user['id'], 'broadcast_message', null, 'Broadcast to ' . $target . '.');
        session()->flash('toast', 'Announcement broadcasted.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/dashboard'));
    }

    public function updateReport(Request $request): void
    {
        $this->requireModeratorPanel();

        [,,, $reportModel, $auditLogModel] = $this->bootAdminModels();
        $reportId = (int) $request->input('report_id', 0);
        $status = trim((string) $request->input('status', 'open'));
        $authUser = auth_user();

        $classScope = is_admin() ? '' : (string) ($authUser['class_name'] ?? '');

        if ($reportId > 0 && $reportModel->updateStatus($reportId, $status, $classScope) && is_array($authUser)) {
            $auditLogModel->record((int) $authUser['id'], 'updated_report', null, 'Marked report #' . $reportId . ' as ' . $status . '.');
            session()->flash('toast', 'Report updated.');
            session()->flash('toast_type', 'success');
        }

        $this->redirect(url('/admin/chats'));
    }

    public function auditLogs(Request $request): void
    {
        $this->requireAuthorizedAdmin();

        [,,,, $auditLogModel] = $this->bootAdminModels();

        $this->view('admin.audit-logs', [
            'title' => 'Audit Logs | LetsChat',
            'activeAdminPage' => 'audit',
            'auditLogs' => $auditLogModel->recent(100),
        ]);
    }

    public function toggleBan(Request $request): void
    {
        $this->requireAuthorizedAdmin();

        $userId = (int) $request->input('user_id', 0);
        $isActive = (int) $request->input('is_active', 0);
        $redirectTo = (string) $request->input('redirect_to', url('/admin/users'));
        if (!str_starts_with($redirectTo, '/admin/')) {
            $redirectTo = url('/admin/users');
        }

        if ($userId <= 0 || !in_array($isActive, [0, 1], true)) {
            session()->flash('toast', 'Unable to update that account.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $userModel = new User();
        $auditLogModel = new AdminAuditLog();
        $userModel->migrate();
        $auditLogModel->migrate();
        $targetUser = $userModel->find($userId);

        if (!$userModel->setBanState($userId, $isActive)) {
            session()->flash('toast', 'Unable to update that account.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $authUser = auth_user();
        if (is_array($authUser)) {
            $auditLogModel->record(
                (int) $authUser['id'],
                $isActive === 1 ? 'unbanned_user' : 'banned_user',
                $userId,
                ($targetUser['email'] ?? 'Unknown account') . ' was ' . ($isActive === 1 ? 'unbanned' : 'banned') . '.'
            );
        }

        if (is_array($authUser) && (int) $authUser['id'] === $userId) {
            $updatedUser = $userModel->find($userId);
            if ($updatedUser !== null) {
                session()->put('user', $updatedUser);
            }
        }

        session()->flash('toast', $isActive === 1 ? 'Account unbanned.' : 'Account banned.');
        session()->flash('toast_type', 'success');
        $this->redirect($redirectTo !== '' ? $redirectTo : url('/admin/users'));
    }

    public function muteUser(Request $request): void
    {
        $this->requireModeratorPanel();

        $userId = (int) $request->input('user_id', 0);
        $duration = trim((string) $request->input('duration', ''));
        $reason = trim((string) $request->input('reason', ''));
        $redirectTo = (string) $request->input('redirect_to', url('/admin/users'));
        if (!str_starts_with($redirectTo, '/admin/')) {
            $redirectTo = url('/admin/users');
        }

        $durations = [
            '1h' => '+1 hour',
            '24h' => '+24 hours',
            '7d' => '+7 days',
        ];
        if ($userId <= 0 || !isset($durations[$duration])) {
            session()->flash('toast', 'Choose a valid mute duration.');
            session()->flash('toast_type', 'error');
            $this->redirect($redirectTo);
        }

        $userModel = new User();
        $auditLogModel = new AdminAuditLog();
        $userModel->migrate();
        $auditLogModel->migrate();
        $targetUser = $userModel->find($userId);
        $authUser = auth_user();

        if ($targetUser === null || !$this->canMuteTarget($authUser, $targetUser)) {
            session()->flash('toast', 'You cannot mute that user.');
            session()->flash('toast_type', 'error');
            $this->redirect($redirectTo);
        }

        $until = date('Y-m-d H:i:s', strtotime($durations[$duration]));
        if ($userModel->muteUntil($userId, (int) ($authUser['id'] ?? 0), $until, $reason)) {
            $auditLogModel->record((int) ($authUser['id'] ?? 0), 'muted_user', $userId, ($targetUser['email'] ?? 'User') . ' muted until ' . $until . '.');
            session()->flash('toast', 'User muted until ' . date('M j, g:i A', strtotime($until)) . '.');
            session()->flash('toast_type', 'success');
        }

        $this->redirect($redirectTo);
    }

    public function unmuteUser(Request $request): void
    {
        $this->requireModeratorPanel();

        $userId = (int) $request->input('user_id', 0);
        $redirectTo = (string) $request->input('redirect_to', url('/admin/users'));
        if (!str_starts_with($redirectTo, '/admin/')) {
            $redirectTo = url('/admin/users');
        }

        $userModel = new User();
        $auditLogModel = new AdminAuditLog();
        $userModel->migrate();
        $auditLogModel->migrate();
        $targetUser = $userModel->find($userId);
        $authUser = auth_user();

        if ($targetUser === null || !$this->canMuteTarget($authUser, $targetUser)) {
            session()->flash('toast', 'You cannot unmute that user.');
            session()->flash('toast_type', 'error');
            $this->redirect($redirectTo);
        }

        if ($userModel->unmute($userId)) {
            $auditLogModel->record((int) ($authUser['id'] ?? 0), 'unmuted_user', $userId, ($targetUser['email'] ?? 'User') . ' was unmuted.');
            session()->flash('toast', 'User unmuted.');
            session()->flash('toast_type', 'success');
        }

        $this->redirect($redirectTo);
    }

    public function deleteMessage(Request $request): void
    {
        $this->requireModeratorPanel();

        $messageId = (int) $request->input('message_id', 0);
        $redirectTo = (string) $request->input('redirect_to', url('/admin/chats'));
        if (!str_starts_with($redirectTo, '/admin/')) {
            $redirectTo = url('/admin/chats');
        }

        if ($messageId <= 0) {
            session()->flash('toast', 'Unable to delete that message.');
            session()->flash('toast_type', 'error');
            $this->redirect($redirectTo);
        }

        $messageModel = new Message();
        $auditLogModel = new AdminAuditLog();
        $messageModel->migrate();
        $auditLogModel->migrate();

        $authUser = auth_user();
        $classScope = is_admin() ? '' : (string) ($authUser['class_name'] ?? '');

        if (!$messageModel->deleteAsAdmin($messageId, $classScope)) {
            session()->flash('toast', 'Unable to delete that message.');
            session()->flash('toast_type', 'error');
            $this->redirect($redirectTo);
        }

        if (is_array($authUser)) {
            $auditLogModel->record((int) $authUser['id'], 'deleted_message', null, 'Deleted message #' . $messageId . ' from chat review.');
        }

        session()->flash('toast', 'Message deleted.');
        session()->flash('toast_type', 'success');
        $this->redirect($redirectTo);
    }

    public function createRoom(Request $request): void
    {
        $this->requireAuthorizedAdmin();

        $roomModel = new Room();
        $roomModel->migrate();

        $name = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));
        $scope = trim((string) $request->input('scope', 'public'));
        $className = trim((string) $request->input('class_name', ''));
        $accentColor = trim((string) $request->input('accent_color', '#2563eb'));
        $password = trim((string) $request->input('password', ''));

        if ($name === '') {
            session()->flash('toast', 'Room name is required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/dashboard'));
        }

        if (!in_array($scope, ['public', 'class'], true)) {
            $scope = 'public';
        }

        if ($scope === 'class' && !in_array($className, $this->classOptions, true)) {
            session()->flash('toast', 'Choose a valid class for the room.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/dashboard'));
        }

        $user = auth_user();
        $createdRoom = $roomModel->createRoom($user, [
            'name' => $name,
            'description' => $description !== '' ? $description : 'Custom room created by admin.',
            'scope' => $scope,
            'class_name' => $scope === 'class' ? $className : null,
            'accent_color' => $accentColor,
            'password' => $password,
        ]);

        if ($createdRoom === null) {
            session()->flash('toast', 'Unable to create that room.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/dashboard'));
        }

        session()->flash('toast', 'Room created successfully.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/rooms'));
    }

    private function requireCentralAdmin(): void
    {
        if (!is_admin()) {
            session()->flash('toast', 'Central admin access required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/chats'));
        }

        if (!is_admin_verified()) {
            session()->flash('toast', 'Confirm your password to open the admin panel.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/auth'));
        }
    }

    private function requireAuthorizedAdmin(): void
    {
        if (!is_admin()) {
            session()->flash('toast', 'Central admin access required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/chats'));
        }

        if (!is_admin_verified()) {
            session()->flash('toast', 'Confirm your password to open the admin panel.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/auth'));
        }
    }

    private function registerFailedAdminAttempt(): void
    {
        session()->forget('admin_verified_at');

        $attempts = session()->get('admin_auth_attempts', ['count' => 0, 'time' => time()]);
        $count = ((int) ($attempts['count'] ?? 0)) + 1;

        if ($count >= self::ADMIN_AUTH_FAILURE_LIMIT) {
            session()->destroy();
            session()->flash('errors', [
                'auth' => 'Unauthorized admin access detected. Please log in again.',
            ]);
            session()->flash('toast', 'Unauthorized admin access detected.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/login'));
        }

        session()->put('admin_auth_attempts', [
            'count' => $count,
            'time' => $count === 1 ? time() : (int) ($attempts['time'] ?? time()),
        ]);

        $remainingAttempts = self::ADMIN_AUTH_FAILURE_LIMIT - $count;
        $attemptText = $remainingAttempts === 1 ? 'attempt' : 'attempts';

        session()->flash('errors', [
            'password' => 'Admin verification failed. ' . $remainingAttempts . ' ' . $attemptText . ' remaining.',
        ]);
        $this->redirect(url('/admin/auth'));
    }

    public function settings(Request $request): void
    {
        $this->requireAuthorizedAdmin();
        $settings = new AppSetting();
        $settings->migrate();
        $invites = new InviteCode();
        $invites->migrate();

        $this->view('admin.settings', [
            'title' => 'Admin Settings | LivingSpring',
            'activeAdminPage' => 'settings',
            'flaggedTerms' => $settings->getFlaggedTerms(),
            'signupRequiresInvite' => $settings->signupRequiresInvite(),
            'invites' => $invites->all(),
            'classOptions' => $this->classOptions,
            'mailEnabled' => (bool) (config('mail')['enabled'] ?? false),
        ]);
    }

    public function saveSettings(Request $request): void
    {
        $this->requireAuthorizedAdmin();
        $settings = new AppSetting();
        $settings->migrate();

        $termsRaw = trim((string) $request->input('flagged_terms', ''));
        $terms = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $termsRaw) ?: []));
        $settings->setFlaggedTerms($terms);
        $settings->setSignupRequiresInvite((string) $request->input('signup_requires_invite', '') === '1');

        session()->flash('toast', 'Settings saved.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/settings'));
    }

    public function createInvite(Request $request): void
    {
        $this->requireAuthorizedAdmin();
        $user = auth_user();
        $invites = new InviteCode();
        $invites->migrate();

        $code = trim((string) $request->input('code', ''));
        if ($code === '') {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        $className = trim((string) $request->input('class_name', ''));
        $maxUses = max(1, (int) $request->input('max_uses', 1));
        $expires = trim((string) $request->input('expires_at', ''));

        $invites->create($code, $className, $maxUses, $expires !== '' ? date('Y-m-d H:i:s', strtotime($expires)) : null, (int) ($user['id'] ?? 0));

        session()->flash('toast', 'Invite code created: ' . strtoupper($code));
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/settings'));
    }

    public function importUsers(Request $request): void
    {
        $this->requireAuthorizedAdmin();
        [$userModel,,,, $auditLogModel] = $this->bootAdminModels();
        $user = auth_user();
        $file = $request->file('csv');
        if ($file === null || (int) ($file['error'] ?? 4) !== UPLOAD_ERR_OK) {
            session()->flash('toast', 'Upload a CSV file (name,email,class,password).');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/users'));
        }

        $handle = fopen((string) $file['tmp_name'], 'r');
        if ($handle === false) {
            $this->redirect(url('/admin/users'));
        }

        $imported = 0;
        $rowNum = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($rowNum === 1 && isset($row[0]) && stripos((string) $row[0], 'name') !== false) {
                continue;
            }
            if (count($row) < 4) {
                continue;
            }
            [$name, $email, $className, $password] = array_map('trim', $row);
            if ($name === '' || $email === '' || $password === '') {
                continue;
            }
            if ($userModel->findByEmail($email) !== null) {
                continue;
            }
            if (!in_array($className, $this->classOptions, true)) {
                $className = 'JSS1';
            }
            if ($userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'class_name' => $className,
                'role' => 'student',
            ]) !== null) {
                $imported++;
            }
        }
        fclose($handle);

        if (is_array($user)) {
            $auditLogModel->record((int) $user['id'], 'import_users', null, 'Imported ' . $imported . ' users from CSV.');
        }

        session()->flash('toast', $imported . ' accounts imported.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/admin/users'));
    }

    public function exportAuditLogs(Request $request): void
    {
        $this->requireAuthorizedAdmin();
        [,,,, $auditLogModel] = $this->bootAdminModels();
        $logs = $auditLogModel->recent(5000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-logs.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'admin', 'action', 'target', 'details', 'created_at']);
        foreach ($logs as $log) {
            fputcsv($out, [
                $log['id'] ?? '',
                $log['admin_name'] ?? '',
                $log['action'] ?? '',
                $log['target_name'] ?? '',
                $log['details'] ?? '',
                $log['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    private function requireModeratorPanel(): void
    {
        if (!has_moderator_privileges()) {
            session()->flash('toast', 'Moderator access required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/feed'));
        }

        if (is_admin() && !is_admin_verified()) {
            session()->flash('toast', 'Confirm your password to open the admin panel.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/auth'));
        }
    }

    private function canMuteTarget(?array $authUser, array $targetUser): bool
    {
        if (!is_array($authUser) || (int) ($authUser['id'] ?? 0) === (int) ($targetUser['id'] ?? 0)) {
            return false;
        }

        $targetRole = (string) ($targetUser['role'] ?? 'student');
        if (is_admin()) {
            return $targetRole !== 'admin';
        }

        return in_array((string) ($authUser['role'] ?? ''), ['class_rep', 'moderator'], true)
            && (string) ($authUser['class_name'] ?? '') === (string) ($targetUser['class_name'] ?? '')
            && $targetRole === 'student';
    }

    private function bootAdminModels(): array
    {
        migrate_app_schema();

        return [new User(), new Room(), new Message(), new MessageReport(), new AdminAuditLog()];
    }
}
