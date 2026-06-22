<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\DmRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\UserBlock;
use Framework\Core\Controller;
use Framework\Core\Request;

class UsersController extends Controller
{
    private array $classOptions = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];

    public function index(Request $request): void
    {
        $term = trim((string) $request->input('q', ''));
        $className = trim((string) $request->input('class', ''));
        $presence = trim((string) $request->input('presence', ''));

        $userModel = new User();
        $userModel->migrate();
        $dmRequestModel = new DmRequest();
        $dmRequestModel->migrate();
        $viewer = auth_user();
        $viewerId = (int) ($viewer['id'] ?? 0);

        if ($viewer === null) {
            $this->redirect(url('/login'));
        }

        $incomingDmRequests = [];
        if ($viewerId > 0) {
            $incomingDmRequests = $dmRequestModel->inboxForUser($viewerId);
        }

        $users = $userModel->search($term, $className, 'unbanned');
        if ($presence === 'online') {
            $users = array_values(array_filter($users, static fn (array $user): bool => (int) ($user['is_online'] ?? 0) === 1));
        } elseif ($presence === 'offline') {
            $users = array_values(array_filter($users, static fn (array $user): bool => (int) ($user['is_online'] ?? 0) !== 1));
        } else {
            $presence = '';
        }

        $blockStatusMap = [];
        if ($viewerId > 0) {
            $blockModel = new UserBlock();
            $blockModel->migrate();
            $viewerBlockedIds = $blockModel->blockedIdsFor($viewerId);
            $viewerBlockedSet = array_fill_keys(array_map('intval', $viewerBlockedIds), true);

            // peer -> viewer blocks (for each directory user) requires per-peer checks.
            // We keep it simple here; users list is paginated/limited by search.
            foreach ($users as $u) {
                $peerId = (int) ($u['id'] ?? 0);
                if ($peerId <= 0) continue;
                $viewerBlockedPeer = isset($viewerBlockedSet[$peerId]);
                $peerBlockedViewer = $blockModel->isBlocked($peerId, $viewerId);
                $blockStatusMap[$peerId] = [
                    'viewer_blocked' => $viewerBlockedPeer,
                    'peer_blocked' => $peerBlockedViewer,
                ];
            }
        }

        $this->view('users.index', [
            'users' => $users,
            'term' => $term,
            'selectedClass' => $className,
            'selectedPresence' => $presence,
            'classOptions' => $this->classOptions,
            'dmRequestStatusMap' => $viewerId > 0 ? $dmRequestModel->statusMapForUser($viewerId) : [],
            'incomingDmRequests' => $incomingDmRequests,
            'viewerId' => $viewerId,
            'blockStatusMap' => $blockStatusMap,
        ]);

    }

    public function requestDm(Request $request): void
    {
        $viewer = auth_user();
        if ($viewer === null) {
            $this->redirect(url('/login'));
        }

        $peerId = (int) $request->input('peer_id', 0);
        $redirectTo = $this->safeUsersRedirect((string) $request->input('redirect_to', url('/users')));
        $dmRequestModel = new DmRequest();
        $dmRequestModel->migrate();
        $userModel = new User();
        $userModel->migrate();
        $blockModel = new UserBlock();
        $blockModel->migrate();
        $peer = $userModel->find($peerId);

        if ($peer === null || (int) $peer['id'] === (int) $viewer['id']) {
            $this->formRespond($request, false, 'Choose a valid person to message.', $redirectTo);
        }

        if ($blockModel->isBlocked((int) $viewer['id'], $peerId) || $blockModel->isBlocked($peerId, (int) $viewer['id'])) {
            $this->formRespond($request, false, 'You cannot message this user.', $redirectTo);
        }

        if ($dmRequestModel->createPending((int) $viewer['id'], $peerId)) {
            $this->formRespond(
                $request,
                true,
                'DM request sent to ' . $peer['name'] . '.',
                $redirectTo,
                [
                    'peer_id' => $peerId,
                    'status' => 'pending',
                    'dmRequests' => $this->dmRequestsPayload((int) $viewer['id']),
                ],
            );
        }

        $this->formRespond($request, false, 'Unable to send that DM request.', $redirectTo);
    }

    public function respondDm(Request $request): void
    {
        $viewer = auth_user();
        if ($viewer === null) {
            $this->redirect(url('/login'));
        }

        $requestId = (int) $request->input('request_id', 0);
        $decision = trim((string) $request->input('decision', ''));
        $dmRequestModel = new DmRequest();
        $dmRequestModel->migrate();
        $dmRequest = $dmRequestModel->findPendingForRecipient($requestId, (int) $viewer['id']);

        if ($dmRequest === null || !in_array($decision, ['accepted', 'declined'], true)) {
            $this->formRespond($request, false, 'Unable to update that DM request.', url('/chat'));
        }

        $roomId = null;
        $redirectRoom = '';
        if ($decision === 'accepted') {
            $userModel = new User();
            $roomModel = new Room();
            $userModel->migrate();
            $roomModel->migrate();
            $requester = $userModel->find((int) $dmRequest['requester_id']);
            if ($requester !== null) {
                $room = $roomModel->createDirectRoom($viewer, $requester);
                $roomId = (int) ($room['id'] ?? 0);
                $redirectRoom = (string) ($room['slug'] ?? '');
            }
        }

        $dmRequestModel->respond($requestId, (int) $viewer['id'], $decision, $roomId);
        $redirectUrl = url('/chat' . ($redirectRoom !== '' ? '?room=' . urlencode($redirectRoom) : ''));
        $this->formRespond(
            $request,
            true,
            $decision === 'accepted' ? 'DM request accepted.' : 'DM request declined.',
            $redirectUrl,
            [
                'decision' => $decision,
                'room_slug' => $redirectRoom,
                'redirect_url' => $redirectUrl,
                'dmRequests' => $this->dmRequestsPayload((int) $viewer['id']),
            ],
        );
    }

    public function blockUser(Request $request): void
    {
        $viewer = auth_user();
        if ($viewer === null) {
            $this->redirect(url('/login'));
        }

        $peerId = (int) $request->input('user_id', 0);
        $redirectTo = $this->safeUsersRedirect((string) $request->input('redirect_to', url('/users')));
        $blockModel = new UserBlock();
        $blockModel->migrate();
        $blockModel->block((int) $viewer['id'], $peerId);

        session()->flash('toast', 'User blocked.');
        session()->flash('toast_type', 'success');
        $this->redirect($redirectTo);
    }

    public function unblockUser(Request $request): void
    {
        $viewer = auth_user();
        if ($viewer === null) {
            $this->redirect(url('/login'));
        }

        $peerId = (int) $request->input('user_id', 0);
        $redirectTo = $this->safeUsersRedirect((string) $request->input('redirect_to', url('/users')));
        $blockModel = new UserBlock();
        $blockModel->migrate();
        $blockModel->unblock((int) $viewer['id'], $peerId);

        session()->flash('toast', 'User unblocked.');
        session()->flash('toast_type', 'success');
        $this->redirect($redirectTo);
    }

    private function safeUsersRedirect(string $redirectTo): string
    {
        $usersUrl = url('/users');
        if ($redirectTo === '' || str_starts_with($redirectTo, 'http')) {
            return $usersUrl;
        }

        $path = (string) parse_url($redirectTo, PHP_URL_PATH);

        return str_ends_with($path, '/users') ? $redirectTo : $usersUrl;
    }

    private function isJsonRequest(Request $request): bool
    {
        return (string) $request->input('format', '') === 'json';
    }

    private function formRespond(Request $request, bool $ok, string $message, string $redirectUrl, array $extra = []): void
    {
        if ($this->isJsonRequest($request)) {
            $this->json(array_merge(['ok' => $ok, 'message' => $message], $extra));

            return;
        }

        session()->flash('toast', $message);
        session()->flash('toast_type', $ok ? 'success' : 'error');
        $this->redirect($redirectUrl);
    }

    private function dmRequestsPayload(int $userId): array
    {
        $dmRequestModel = new DmRequest();
        $dmRequestModel->migrate();

        return [
            'inbox' => $dmRequestModel->inboxForUser($userId),
            'sent' => $dmRequestModel->sentForUser($userId),
        ];
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
