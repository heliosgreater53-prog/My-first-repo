<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Room;
use Framework\Core\Controller;
use Framework\Core\Request;

class RoomAdminController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAdmin();

        $roomModel = new Room();
        $roomModel->migrate();
        $rooms = $roomModel->allForAdmin();

        $this->view('admin.rooms', [
            'title' => 'Manage Rooms | Admin',
            'activeAdminPage' => 'rooms',
            'rooms' => $rooms,
        ]);
    }

    public function update(Request $request): void
    {
        $this->requireAdmin();

        $roomId = (int) $request->input('room_id', 0);
        $name = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));
        $accentColor = trim((string) $request->input('accent_color', '#2563eb'));
        $password = trim((string) $request->input('password', ''));

        if ($roomId <= 0 || $name === '') {
            session()->flash('toast', 'Invalid room data.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/rooms'));
        }

        $roomModel = new Room();
        $roomModel->migrate();
        $ok = $roomModel->update($roomId, [
            'name' => $name,
            'description' => $description,
            'accent_color' => $accentColor,
            'password' => $password,
        ]);

        if ($ok) {
            session()->flash('toast', 'Room updated successfully.');
            session()->flash('toast_type', 'success');
        } else {
            session()->flash('toast', 'Failed to update room.');
            session()->flash('toast_type', 'error');
        }

        $this->redirect(url('/admin/rooms'));
    }

    public function delete(Request $request): void
    {
        $this->requireAdmin();

        $roomId = (int) $request->input('room_id', 0);

        if ($roomId <= 0) {
            session()->flash('toast', 'Invalid room ID.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/admin/rooms'));
        }

        $roomModel = new Room();
        $roomModel->migrate();
        $ok = $roomModel->delete($roomId);

        if ($ok) {
            session()->flash('toast', 'Room deleted successfully.');
            session()->flash('toast_type', 'success');
        } else {
            session()->flash('toast', 'Failed to delete room.');
            session()->flash('toast_type', 'error');
        }

        $this->redirect(url('/admin/rooms'));
    }

    private function requireAdmin(): void
    {
        $user = auth_user();
        if ($user === null || !has_admin_privileges()) {
            session()->flash('toast', 'Admin access required.');
            session()->flash('toast_type', 'error');
            $this->redirect(url('/chat'));
        }
    }
}
