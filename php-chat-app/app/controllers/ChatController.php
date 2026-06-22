<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use App\Models\MessageReport;
use App\Models\DmRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserNotification;
use App\Models\PostActions;
use Framework\Core\Controller;
use Framework\Core\Request;

class ChatController extends Controller
{
    private array $classOptions = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];

    public function index(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel, $messageModel, $userModel, $dmRequestModel] = $this->bootChatModels();

        $requestedSlug = trim((string) $request->input('room', ''));
        $currentPath = $request->path();
        $isAdmin = has_admin_privileges();
        $rooms = $roomModel->accessibleForUser($user, $isAdmin);
        $isExploreView = $currentPath === '/communities' && ($requestedSlug === '' || $requestedSlug === 'home');
        $isRoomView = !$isExploreView && $requestedSlug !== '' && $requestedSlug !== 'home';
        $isGlobalFeed = !$isRoomView && !$isExploreView;
        $layoutMode = $isExploreView ? 'explore' : ($isRoomView ? 'room' : 'feed');

        $activeRoom = $isRoomView
            ? $roomModel->findVisibleBySlug($user, $requestedSlug, $isAdmin)
            : [
                'name' => $isExploreView ? 'Communities' : 'School Feed',
                'slug' => 'home',
                'id' => 0,
                'description' => $isExploreView
                    ? 'Browse and join conversation spaces across LivingSpring.'
                    : 'Public updates from communities you can access.',
            ];

        if ($activeRoom === null && $isRoomView) {
            session()->flash('toast', 'No room is available for your account yet.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/profile'));
        }

        $requiresRoomPassword = $isRoomView && $roomModel->requiresPasswordForUser($activeRoom, $user, $isAdmin);
        $feedClass = trim((string) $request->input('class', ''));
        $feedFilter = trim((string) $request->input('filter', 'all'));
        if (!in_array($feedFilter, ['all', 'announcements', 'assignments'], true)) {
            $feedFilter = 'all';
        }
        $messages = [];
        $replyMessage = null;
        $editMessage = null;
        $replyId = (int) $request->input('reply', 0);
        $editId = (int) $request->input('edit', 0);

        if (!$requiresRoomPassword && !$isExploreView) {
            if ($isRoomView) {
                $roomModel->markRead((int) $activeRoom['id'], (int) $user['id']);
            }
            $rooms = $roomModel->accessibleForUser($user, $isAdmin);

            $messages = $isGlobalFeed
                ? $messageModel->allPublicLatest(80, $feedClass, $feedFilter)
                : $messageModel->latestForRoom((int) $activeRoom['id']);

            if ($replyId > 0 && $isRoomView) {
                $replyMessage = $messageModel->findInRoom((int) $activeRoom['id'], $replyId);
            }

            if ($editId > 0 && $isRoomView) {
                $candidate = $messageModel->findInRoom((int) $activeRoom['id'], $editId);
                if ($candidate !== null && (int) $candidate['user_id'] === (int) $user['id'] && empty($candidate['deleted_at'])) {
                    $editMessage = $candidate;
                }
            }
        }

        $postModel = new \App\Models\Post();
        $posts = $isGlobalFeed
            ? $postModel->allLatest(80, $feedClass, $feedFilter)
            : [];

        $pageTitle = match ($layoutMode) {
            'explore' => 'Communities | LivingSpring',
            'feed' => 'School Feed | LivingSpring',
            default => (string) ($activeRoom['name'] ?? 'Room') . ' | LivingSpring',
        };

        $pinnedMessages = [];
        if ($isRoomView && !$requiresRoomPassword && (int) ($activeRoom['id'] ?? 0) > 0) {
            $pinnedMessages = $messageModel->pinnedForRoom((int) $activeRoom['id']);
        }

        $notifyModel = new UserNotification();
        $notifyModel->migrate();
        $notificationCount = (int) $notifyModel->unreadCount((int) $user['id']);
        if ((int) ($user['notifications_enabled'] ?? 1) !== 1) {
            $notificationCount = 0;
        }

        $this->view('chat.index', [
            'title' => $pageTitle,
            'user' => $user,
            'rooms' => $rooms,
            'activeRoom' => $activeRoom,
            'messages' => $messages,
            'posts' => $posts ?? [],
            'pinnedMessages' => $pinnedMessages,
            'replyMessage' => $replyMessage,
            'editMessage' => $editMessage,
            'requiresRoomPassword' => $requiresRoomPassword,
            'classOptions' => $this->classOptions,
            'feedComposeRooms' => [],
            'feedClassFilter' => $feedClass ?? '',
            'feedViewFilter' => $feedFilter ?? 'all',
            'chatUsers' => $userModel->peersForChat((int) $user['id']),
            'onlineUsers' => $userModel->getOnlineInRoom((int) $activeRoom['id']),
            'dmInboxRequests' => $dmRequestModel->inboxForUser((int) $user['id']),
            'dmSentRequests' => $dmRequestModel->sentForUser((int) $user['id']),
            'notificationCount' => $notificationCount,
            'layoutMode' => $layoutMode,
            'isExploreView' => $isExploreView,
            'isGlobalFeed' => $isGlobalFeed,
            'currentPath' => $currentPath,
        ]);;
    }

    public function joinRoom(Request $request): void
    {
        $user = $this->requireUser();
        $roomSlug = trim((string) $request->input('slug', ''));
        $password = trim((string) $request->input('password', ''));

        [$roomModel] = $this->bootChatModels();
        $room = $roomModel->findBySlug($roomSlug);

        if ($room === null || $roomModel->findVisibleBySlug($user, $roomSlug, has_admin_privileges()) === null) {
            session()->flash('toast', 'Room not found.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        if (!has_admin_privileges() && !$roomModel->verifyPassword((int) $room['id'], $password)) {
            session()->flash('toast', 'Incorrect room password.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat?room=' . urlencode($roomSlug)));
        }

        $roomModel->ensureMembership((int) $room['id'], (int) $user['id']);
        session()->flash('toast', 'Joined password-protected room successfully.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/chat?room=' . urlencode($roomSlug)));
    }

    public function createPost(Request $request): void
    {
        $user = $this->requireUser();

        $postModel = new \App\Models\Post();

        $body = trim((string) $request->input('message', ''));

        $userModel = new \App\Models\User();
        $freshUser = $userModel->find((int) ($user['id'] ?? 0));

        if ($freshUser !== null && $userModel->isMuted($freshUser)) {
            $mutedUntil = date('M j, g:i A', strtotime((string) $freshUser['muted_until']));
            $this->formRespond(
                $request,
                false,
                'You are muted until ' . $mutedUntil . ' and cannot post right now.',
                url('/feed'),
            );
        }

        if ($body === '') {
            $this->formRespond($request, false, 'Type a message before posting.', url('/feed'));
        }

        if (mb_strlen($body) > 2000) {
            $this->formRespond($request, false, 'Messages must be 2000 characters or less.', url('/feed'));
        }

        $postType = trim((string) $request->input('post_type', 'message'));
        if (!in_array($postType, ['message', 'announcement', 'assignment'], true)) {
            $postType = 'message';
        }
        if ($postType === 'announcement' && !has_admin_privileges()) {
            $postType = 'message';
        }

        $assignmentTitle = trim((string) $request->input('assignment_title', ''));
        $dueAt = trim((string) $request->input('due_at', ''));
        $scheduledAt = trim((string) $request->input('scheduled_at', ''));
        $dueAtDb = $dueAt !== '' ? date('Y-m-d H:i:s', strtotime($dueAt)) : null;
        $scheduledAtDb = $scheduledAt !== '' ? date('Y-m-d H:i:s', strtotime($scheduledAt)) : null;

        $attachment = $this->storeChatAttachment($request);

        $postId = $postModel->create(
            (int) $user['id'],
            $body,
            $postType,
            $assignmentTitle !== '' ? $assignmentTitle : null,
            $dueAtDb,
            $scheduledAtDb,
            $attachment,
        );

$this->formRespond(
            $request,
            true,
            'Post published.',
            url('/feed'),
            ['post_id' => $postId],
        );
    }

    public function deletePost(Request $request): void
    {
        $user = $this->requireUser();
        $postModel = new \App\Models\Post();

        $postId = (int) $request->input('post_id', 0);

        if ($postId <= 0) {
            $this->formRespond($request, false, 'Invalid post.', url('/feed'));
        }

        if (!$postModel->deleteIfAllowed($postId, (int) $user['id'], has_admin_privileges())) {
            $this->formRespond($request, false, 'Unable to delete that post.', url('/feed'));
        }

        $this->formRespond($request, true, 'Post deleted.', url('/feed'));
    }

    public function send(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel, $messageModel, $userModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $body = trim((string) $request->input('message', ''));
        $replyToId = (int) $request->input('reply_to_id', 0);

        $freshUser = $userModel->find((int) ($user['id'] ?? 0));
        if ($freshUser !== null && $userModel->isMuted($freshUser)) {
            $mutedUntil = date('M j, g:i A', strtotime((string) $freshUser['muted_until']));
            $this->formRespond(
                $request,
                false,
                'You are muted until ' . $mutedUntil . ' and cannot post right now.',
                url('/feed'),
            );
        }

        if ($body === '') {
            $this->formRespond($request, false, 'Type a message before posting.', url('/feed'));
        }

        if (mb_strlen($body) > 2000) {
            $this->formRespond($request, false, 'Messages must be 2000 characters or less.', url('/feed'));
        }

        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);
        if ($room === null) {
            $this->formRespond($request, false, 'Choose a room to post in.', url('/feed'));
        }

        $attachment = $this->storeChatAttachment($request);
        $roomId = (int) ($room['id'] ?? 0);

        $messageId = $messageModel->create(
            $roomId,
            (int) ($user['id'] ?? 0),
            $body,
            $replyToId > 0 ? $replyToId : null,
            $attachment,
        );

        $chatReturnUrl = url('/chat?room=' . urlencode($roomSlug));
        $this->formRespond($request, true, 'Message sent.', $chatReturnUrl, ['message_id' => $messageId]);
    }

    public function editMessage(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel, $messageModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $messageId = (int) $request->input('message_id', 0);
        $body = trim((string) $request->input('message', ''));
        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);

        $chatReturnUrl = url('/chat?room=' . urlencode($roomSlug));

        if ($room === null || $messageId <= 0) {
            $this->formRespond($request, false, 'Unable to edit that message.', url('/chat'));
        }

        if ($body === '') {
            $this->formRespond($request, false, 'Edited message cannot be empty.', url('/chat?room=' . urlencode($roomSlug) . '&edit=' . $messageId));
        }

        if (mb_strlen($body) > 2000) {
            $this->formRespond($request, false, 'Messages must be 2000 characters or less.', url('/chat?room=' . urlencode($roomSlug) . '&edit=' . $messageId));
        }

        if (!$messageModel->updateIfAllowed($messageId, (int) $room['id'], (int) $user['id'], $body)) {
            $this->formRespond($request, false, 'Only your own active messages can be edited.', $chatReturnUrl);
        }

        $this->formRespond($request, true, 'Message updated.', $chatReturnUrl, ['message_id' => $messageId]);
    }

    public function createRoom(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel,, $userModel] = $this->bootChatModels();

        $scope = trim((string) $request->input('scope', 'public'));
        $roomName = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));
        $className = trim((string) $request->input('class_name', ''));
        $peerId = (int) $request->input('peer_id', 0);

        if ($scope === 'direct') {
            $peer = null;
            foreach ($userModel->peersForChat((int) $user['id']) as $candidate) {
                if ((int) $candidate['id'] === $peerId) {
                    $peer = $candidate;
                    break;
                }
            }

            if ($peer === null) {
                session()->flash('toast', 'Choose a valid user for direct chat.');
                session()->flash('toast_type', 'error');
                $this->redirect(url('/chat'));
            }

            $room = $roomModel->createDirectRoom($user, $peer);
            $this->redirect(url('/chat?room=' . urlencode((string) ($room['slug'] ?? ''))));
        }

        if ($roomName === '') {
            session()->flash('toast', 'Room name is required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        if ($scope === 'class' && !in_array($className, $this->classOptions, true)) {
            session()->flash('toast', 'Choose a valid class for the room.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        if ($scope !== 'direct' && !has_admin_privileges()) {
            $pwd = trim((string) $request->input('password', ''));
            if ($pwd !== '') {
                session()->flash('toast', 'Only admins can set a room password.');
                session()->flash('toast_type', 'error');
                $this->redirect(url('/chat'));
            }
        }

        $room = $roomModel->createRoom($user, [
            'name' => $roomName,
            'description' => $description !== '' ? $description : 'Custom room inside LetsChat.',
            'scope' => in_array($scope, ['public', 'class'], true) ? $scope : 'public',
            'class_name' => $scope === 'class' ? $className : null,
            'accent_color' => '#2f6c5e',
            'password' => $scope === 'direct' ? '' : trim((string) $request->input('password', '')),
        ]);

        if ($room === null) {
            session()->flash('toast', 'Unable to create that room.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        session()->flash('toast', 'Room created successfully.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/chat?room=' . urlencode((string) $room['slug'])));
    }

    public function deleteMessage(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel, $messageModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $messageId = (int) $request->input('message_id', 0);
        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);

        if ($room === null || $messageId <= 0) {
            session()->flash('toast', 'Unable to delete that message.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        $messageModel->deleteIfAllowed($messageId, (int) $room['id'], (int) $user['id'], has_admin_privileges());

        session()->flash('toast', 'Message deleted.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/chat?room=' . urlencode($roomSlug)));
    }

    public function toggleReaction(Request $request): void
    {
        $user = $this->requireUser();
        $messageId = (int) $request->input('message_id', 0);
        $reaction = trim((string) $request->input('reaction', ''));
        $roomSlug = trim((string) $request->input('room', ''));

        $messageModel = new Message();
        $messageModel->migrate();
        $ok = $messageId > 0 && $messageModel->toggleReaction($messageId, (int) $user['id'], $reaction);

        if ($this->isStreamRequest($request)) {
            $this->json(['ok' => $ok]);
        }

        $this->redirect(url('/chat' . ($roomSlug !== '' ? '?room=' . urlencode($roomSlug) : '')));
    }

    public function togglePin(Request $request): void
    {
        $user = $this->requireUser();
        [$roomModel, $messageModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $messageId = (int) $request->input('message_id', 0);
        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);

        if ($room !== null && $messageId > 0) {
            $messageModel->togglePin($messageId, (int) $room['id'], (int) $user['id'], has_admin_privileges());
        }

        $this->redirect(url('/chat?room=' . urlencode($roomSlug)));
    }

    public function typing(Request $request): void
    {
        $user = $this->requireUser();
        [$roomModel, $messageModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);
        if ($room !== null) {
            $messageModel->markTyping((int) $room['id'], (int) $user['id']);
        }

        $this->json(['ok' => $room !== null]);
    }

    // Poll-based “recording audio” indicator.
    // For now we store it in the same typing_indicators table; UI will show “recording audio...”.
    public function recording(Request $request): void
    {
        $user = $this->requireUser();
        [$roomModel, $messageModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $state = trim((string) $request->input('state', '1'));
        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);

        if ($room !== null) {
            if ($state === '1' || $state === 'true') {
                $messageModel->markTyping((int) $room['id'], (int) $user['id']);
            }
        }

        $this->json(['ok' => $room !== null]);
    }

    public function reportMessage(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel, $messageModel] = $this->bootChatModels();

        $roomSlug = trim((string) $request->input('room', ''));
        $messageId = (int) $request->input('message_id', 0);
        $reason = trim((string) $request->input('reason', 'Reported for review'));
        $room = $roomModel->findAccessibleBySlug($user, $roomSlug);

        if ($room === null || $messageId <= 0 || $messageModel->findInRoom((int) $room['id'], $messageId) === null) {
            session()->flash('toast', 'Unable to report that message.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        $reportModel = new MessageReport();
        $reportModel->migrate();
        $reportModel->create($messageId, (int) $user['id'], $reason !== '' ? $reason : 'Reported for review');

        session()->flash('toast', 'Message reported for admin review.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/chat?room=' . urlencode($roomSlug)));
    }

    public function stream(Request $request): void
    {
        $user = $this->requireUser();

        [$roomModel, $messageModel, $userModel, $dmRequestModel] = $this->bootChatModels();
        $requestedSlug = trim((string) $request->input('room', ''));
        $isGlobalFeed = ($requestedSlug === '' || $requestedSlug === 'home');

        $room = !$isGlobalFeed
            ? $roomModel->findAccessibleBySlug($user, $requestedSlug)
            : ['id' => 0, 'name' => 'Global Feed'];

        if ($room === null) {
            $this->json(['ok' => false]);
            return;
        }

        $feedClass = trim((string) $request->input('class', ''));
        $feedFilter = trim((string) $request->input('filter', 'all'));
        if (!in_array($feedFilter, ['all', 'announcements', 'assignments'], true)) {
            $feedFilter = 'all';
        }

        $messages = $isGlobalFeed
            ? $messageModel->allPublicLatest(80, $feedClass, $feedFilter)
            : $messageModel->latestForRoom((int) $room['id']);
        $rooms = $roomModel->accessibleForUser($user, has_admin_privileges());
        $typingUsers = $isGlobalFeed ? [] : $messageModel->typingUsers((int) $room['id'], (int) $user['id']);
        $notifyModel = new UserNotification();
        $notifyModel->migrate();
        $notificationCount = (int) $notifyModel->unreadCount((int) $user['id']);
        if ((int) ($user['notifications_enabled'] ?? 1) !== 1) {
            $notificationCount = 0;
        }

        $this->json([
            'ok' => true,
            'messages' => $messages,
            'rooms' => $rooms,
            'typing' => $typingUsers,
            // Reuse the same UI “typing indicator” mechanism for audio recording for now.
            // Clients will show a distinct message when recording state is active.
            'recording' => $typingUsers,
            'onlineUsers' => $userModel->getOnlineInRoom((int) $room['id']),
            'dmRequests' => [
                'inbox' => $dmRequestModel->inboxForUser((int) $user['id']),
                'sent' => $dmRequestModel->sentForUser((int) $user['id']),
            ],
            'notificationCount' => $notificationCount,
            'openReportCount' => has_moderator_privileges() ? (new MessageReport())->countOpen(is_admin() ? '' : (string) ($user['class_name'] ?? '')) : 0,
        ]);
    }

    public function notifications(Request $request): void
    {
        $user = $this->requireUser();
        $notifyModel = new UserNotification();
        $notifyModel->migrate();
        $notifyModel->markAllRead((int) $user['id']);

        $this->view('chat.notifications', [
            'title' => 'Notifications | LivingSpring',
            'notifications' => $notifyModel->recentForUser((int) $user['id'], 50),
        ]);
    }

    public function joinCommunity(Request $request): void
    {
        $user = $this->requireUser();
        [$roomModel] = $this->bootChatModels();
        $slug = trim((string) $request->input('slug', ''));
        $room = $roomModel->findVisibleBySlug($user, $slug, has_admin_privileges());

        if ($room === null) {
            session()->flash('toast', 'Room not found.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/communities'));
        }

        if (!empty($room['password_hash']) && !has_admin_privileges()) {
            session()->flash('toast', 'This room requires a password. Open it from chat to join.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat?room=' . urlencode($slug)));
        }

        $roomModel->ensureMembership((int) $room['id'], (int) $user['id']);
        session()->flash('toast', 'Joined ' . ($room['name'] ?? 'room') . '.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/chat?room=' . urlencode($slug)));
    }

    private function bootChatModels(): array
    {
        migrate_app_schema();

        return [new Room(), new Message(), new User(), new DmRequest()];
    }

    private function requireUser(): array
    {
        $user = auth_user();

        if ($user === null) {
            session()->flash('errors', [
                'auth' => 'Please log in to access chat.',
            ]);
            $this->redirect(url('/login'));
        }

        $userModel = new User();
        $userModel->migrate();
        $freshUser = $userModel->find((int) ($user['id'] ?? 0));
        if ($freshUser !== null) {
            session()->put('user', $freshUser);
            return $freshUser;
        }

        return $user;
    }

    private function isStreamRequest(Request $request): bool
    {
        return (string) $request->input('format', '') === 'json';
    }

    private function formRespond(Request $request, bool $ok, string $message, string $redirectUrl, array $extra = []): void
    {
        if ($this->isStreamRequest($request)) {
            $this->json(array_merge(['ok' => $ok, 'message' => $message], $extra));

            return;
        }

        session()->flash('toast', $message);
        session()->flash('toast_type', $ok ? 'success' : 'error');
        $this->redirect($redirectUrl);
    }

    public function getOnlineStatus(Request $request): void
    {
        $user = $this->requireUser();

        $rawIds = $request->input('user_ids', '');
        if (is_string($rawIds) && $rawIds !== '') {
            $userIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        } elseif (is_array($rawIds)) {
            $userIds = array_values(array_filter(array_map('intval', $rawIds)));
        } else {
            $userIds = [];
        }

        if ($userIds === []) {
            $userModel = new User();
            $userModel->migrate();
            $userIds = array_map(
                static fn(array $peer): int => (int) $peer['id'],
                $userModel->peersForChat((int) $user['id'])
            );
        }

        $userModel = new User();
        $onlineStatus = $userModel->getOnlineStatusForUsers($userIds);

        $this->json([
            'online' => $onlineStatus,
        ]);
    }

    public function pingPresence(Request $request): void
    {
        $this->requireUser();

        $this->json([
            'ok' => true,
        ]);
    }

    private function storeChatAttachment(Request $request): array
    {
        $file = $request->file('attachment');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
            session()->flash('toast', 'Attachment upload failed or is larger than 8MB.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        $originalName = basename((string) ($file['name'] ?? 'attachment'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = (string) ($file['type'] ?? '');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'mp3', 'wav', 'ogg', 'webm'];
        if (!in_array($extension, $allowed, true)) {
            session()->flash('toast', 'That attachment type is not allowed.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        $type = str_starts_with($mime, 'image/') ? 'image' : (str_starts_with($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'webm'], true) ? 'audio' : 'file');
        $uploadDir = base_path('public/uploads/chat');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileName = bin2hex(random_bytes(10)) . '.' . $extension;
        $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            session()->flash('toast', 'Unable to save that attachment.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }

        return [
            'path' => public_url('uploads/chat/' . $fileName),
            'type' => $type,
            'name' => $originalName,
        ];
    }

    public function togglePostLike(Request $request): void
    {
        $user = $this->requireUser();
        $postId = (int) $request->input('post_id', 0);

        $actions = new PostActions();
        $actions->migrate();

        $ok = $postId > 0;
        if ($ok) {
            $ok = $actions->toggleLike($postId, (int) ($user['id'] ?? 0));
        }

        $this->json([
            'ok' => $ok,
            'likeCount' => $postId > 0 ? $actions->likeCount($postId) : 0,
        ]);
    }

    public function listPostComments(Request $request): void
    {
        $postId = (int) $request->input('post_id', 0);
        if ($postId <= 0) {
            $this->json(['ok' => false, 'comments' => []]);
        }

        $actions = new PostActions();
        $actions->migrate();

        $this->json([
            'ok' => true,
            'comments' => $actions->commentsTree($postId),
        ]);
    }

    public function getPostLikeCount(Request $request): void
    {
        $postId = (int) $request->input('post_id', 0);
        if ($postId <= 0) {
            $this->json(['ok' => false, 'likeCount' => 0]);
        }

        $actions = new PostActions();
        $actions->migrate();

        $this->json([
            'ok' => true,
            'likeCount' => $actions->likeCount($postId),
        ]);
    }

    public function getPostCommentsCount(Request $request): void
    {
        $postId = (int) $request->input('post_id', 0);
        if ($postId <= 0) {
            $this->json(['ok' => false, 'commentsCount' => 0]);
        }

        $actions = new PostActions();
        $actions->migrate();

        $this->json([
            'ok' => true,
            'commentsCount' => $actions->commentsCount($postId),
        ]);
    }



    public function createPostComment(Request $request): void
    {
        $user = $this->requireUser();
        $postId = (int) $request->input('post_id', 0);
        $parentId = $request->input('parent_id', null);
        $parentId = $parentId !== null ? (int) $parentId : null;
        $body = trim((string) $request->input('body', ''));

        $actions = new PostActions();
        $actions->migrate();

        if ($postId <= 0 || $body === '') {
            $this->json(['ok' => false, 'message' => 'Invalid input']);
        }

        $commentId = $actions->createComment($postId, (int) ($user['id'] ?? 0), $body, $parentId);

        $this->json([
            'ok' => $commentId > 0,
            'commentId' => $commentId,
            'comments' => $commentId > 0 ? $actions->commentsTree($postId) : [],
        ]);
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}

