<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Framework\Core\Controller;
use Framework\Core\Request;

class ProfileController extends Controller
{
    private array $classOptions = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];

    public function index(Request $request): void
    {
        $user = auth_user();

        if ($user === null) {
            session()->flash('errors', [
                'auth' => 'Please log in to view your profile.',
            ]);
            $this->redirect(url('/login'));
        }

        $userModel = new User();
        $userModel->migrate();
        $freshUser = $userModel->find((int) $user['id']) ?? $user;

        $this->view('profile.index', [
            'user' => $freshUser,
            'errors' => session()->getFlash('errors', []),
            'success' => session()->getFlash('success'),
            'classOptions' => $this->classOptions,
        ]);
    }

    public function update(Request $request): void
    {
        $user = auth_user();

        if ($user === null) {
            session()->flash('errors', [
                'auth' => 'Please log in to edit your profile.',
            ]);
            $this->redirect(url('/login'));
        }

        $userModel = new User();
        $userModel->migrate();

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $className = trim((string) $request->input('class_name', ''));
        $roomName = trim((string) $request->input('room_name', ''));
        $status = trim((string) $request->input('status', 'Online'));
        $headline = trim((string) $request->input('headline', ''));
        $bio = trim((string) $request->input('bio', ''));
        $password = (string) $request->input('password', '');
        $avatarPath = $user['avatar_path'] ?? null;
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Full name is required.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($userModel->emailBelongsToAnotherUser($email, (int) $user['id'])) {
            $errors['email'] = 'That email is already in use.';
        }

        if ($roomName === '') {
            $errors['room_name'] = 'Main room is required.';
        }

        if (!in_array($className, $this->classOptions, true)) {
            $errors['class_name'] = 'Select a valid class.';
        }

        $allowedStatuses = ['Online', 'Offline'];
        if (!in_array($status, $allowedStatuses, true)) {
            $errors['status'] = 'Choose a valid status.';
        }

        if (strlen($headline) > 140) {
            $errors['headline'] = 'Headline must be 140 characters or less.';
        }

        if (strlen($bio) > 500) {
            $errors['bio'] = 'Bio must be 500 characters or less.';
        }

        if ($password !== '' && strlen($password) < 6) {
            $errors['password'] = 'New password must be at least 6 characters.';
        }

        $avatar = $request->file('avatar');
        if (is_array($avatar) && (int) ($avatar['error'] ?? 4) !== 4) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $mimeType = mime_content_type((string) $avatar['tmp_name']);

            if (!in_array($mimeType, $allowedTypes, true)) {
                $errors['avatar'] = 'Avatar must be a JPG, PNG, or WEBP image.';
            } else {
                $uploadDirectory = base_path('public/assets/images/avatars');

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }

                $extension = pathinfo((string) $avatar['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'avatar-' . (int) $user['id'] . '-' . time() . '.' . strtolower($extension);
                $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

                if (move_uploaded_file((string) $avatar['tmp_name'], $destination)) {
                    $avatarPath = '/assets/images/avatars/' . $filename;
                } else {
                    $errors['avatar'] = 'Unable to upload avatar right now.';
                }
            }
        }

        if ($errors !== []) {
            session()->flash('errors', $errors);
            $this->redirect(url('/profile'));
        }

        $updatedUser = $userModel->update((int) $user['id'], [
            'name' => $name,
            'email' => $email,
            'class_name' => $className,
            'avatar_path' => $avatarPath,
            'room_name' => $roomName,
            'status' => $status,
            'headline' => $headline,
            'bio' => $bio,
            'password' => $password,
        ]);

        if ($updatedUser === null) {
            session()->flash('errors', [
                'database' => 'Unable to update your profile right now.',
            ]);
            $this->redirect(url('/profile'));
        }

        session()->put('user', $updatedUser);
        session()->flash('toast', 'Your profile has been updated.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/profile'));
    }
}
