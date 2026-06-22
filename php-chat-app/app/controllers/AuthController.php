<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AppSetting;
use App\Models\InviteCode;
use App\Models\PasswordReset;
use App\Models\User;
use Framework\Core\Controller;
use Framework\Core\Request;

class AuthController extends Controller
{
    private array $classOptions = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];

    public function login(Request $request): void
    {
        if (auth_user() !== null) {
            $this->redirect(url('/feed'));
        }

        $this->view('auth.login', [
            'title' => 'Login | LivingSpring',
            'errors' => session()->getFlash('errors', []),
            'success' => session()->getFlash('success'),
        ]);
    }

    public function authenticate(Request $request): void
    {
        $userModel = new User();
        $userModel->migrate();

        $attempts = session()->get('login_attempts', ['count' => 0, 'time' => time()]);
        if (($attempts['count'] ?? 0) >= 5 && time() - (int) ($attempts['time'] ?? time()) < 600) {
            session()->put('old', [
                'email' => trim((string) $request->input('email', '')),
                'remember' => (string) $request->input('remember', '0') === '1' ? '1' : '0',
            ]);
            session()->flash('errors', ['auth' => 'Too many login attempts. Please wait a few minutes and try again.']);
            $this->redirect(url('/login'));
        }

        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $remember = (string) $request->input('remember', '0') === '1';
        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if ($errors !== []) {
            session()->put('old', ['email' => $email, 'remember' => $remember ? '1' : '0']);
            session()->flash('errors', $errors);
            $this->redirect(url('/login'));
        }

        $user = $userModel->findByEmail($email);

        $remember = (string) $request->input('remember', '0') === '1';

        if ($user === null || !password_verify($password, (string) $user['password'])) {
            $attempts = session()->get('login_attempts', ['count' => 0, 'time' => time()]);
            $count = ((int) ($attempts['count'] ?? 0)) + 1;
            session()->put('login_attempts', [
                'count' => $count,
                'time' => $count === 1 ? time() : (int) ($attempts['time'] ?? time()),
            ]);
            session()->put('old', ['email' => $email, 'remember' => $remember ? '1' : '0']);
            session()->flash('errors', ['auth' => 'Invalid email or password.']);
            $this->redirect(url('/login'));
        }

        if ((int) ($user['is_active'] ?? 1) !== 1) {
            session()->put('old', ['email' => $email, 'remember' => $remember ? '1' : '0']);
            session()->flash('errors', ['auth' => 'Your account has been banned. Please contact an admin.']);
            $this->redirect(url('/login'));
        }

        $userModel->markOnline((int) $user['id']);
        $sessionUser = $userModel->find((int) $user['id']) ?? $user;
        unset($sessionUser['password']);

        session()->forget('old');
        session()->forget('login_attempts');
        session()->regenerate();
        session()->put('user', $sessionUser);
        if ($remember) {
            session()->persist();
        }
        session()->flash('toast', 'Welcome back, ' . $sessionUser['name'] . '.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/feed'));
    }

    public function signup(Request $request): void
    {
        if (auth_user() !== null) {
            $this->redirect(url('/feed'));
        }

        $settings = new AppSetting();
        $settings->migrate();

        $this->view('auth.signup', [
            'title' => 'Signup | LivingSpring',
            'errors' => session()->getFlash('errors', []),
            'classOptions' => $this->classOptions,
            'signupRequiresInvite' => $settings->signupRequiresInvite(),
        ]);
    }

    public function store(Request $request): void
    {
        $userModel = new User();
        $userModel->migrate();

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $className = trim((string) $request->input('class_name', ''));
        $inviteCode = strtoupper(trim((string) $request->input('invite_code', '')));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Full name is required.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if (!in_array($className, $this->classOptions, true)) {
            $errors['class_name'] = 'Select a valid class.';
        }

        $remember = (string) $request->input('remember', '0') === '1';

        if ($email !== '' && $userModel->findByEmail($email) !== null) {
            $errors['email'] = 'That email is already registered.';
        }

        $settings = new AppSetting();
        $settings->migrate();
        if ($settings->signupRequiresInvite()) {
            $inviteModel = new InviteCode();
            $inviteModel->migrate();
            if ($inviteCode === '' || !$inviteModel->isValid($inviteCode)) {
                $errors['invite_code'] = 'A valid invite code is required to sign up.';
            }
        }

        if ($errors !== []) {
            session()->put('old', [
                'name' => $name,
                'email' => $email,
                'class_name' => $className,
                'invite_code' => $inviteCode,
                'remember' => $remember ? '1' : '0',
            ]);
            session()->flash('errors', $errors);
            $this->redirect(url('/signup'));
        }

        if ($settings->signupRequiresInvite()) {
            $inviteModel = new InviteCode();
            $inviteModel->migrate();
            $inviteModel->consume($inviteCode);
        }

        $user = $userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'class_name' => $className,
            'role' => $userModel->count() === 0 ? 'admin' : 'student',
        ]);

        if ($user === null) {
            session()->put('old', [
                'name' => $name,
                'email' => $email,
                'class_name' => $className,
            ]);
            session()->flash('errors', [
                'database' => 'Unable to create your account right now. Check your DB settings in config/db.php.',
            ]);
            $this->redirect(url('/signup'));
        }

        $userModel->markOnline((int) $user['id']);
        $user = $userModel->find((int) $user['id']) ?? $user;

        session()->forget('old');
        session()->regenerate();
        session()->put('user', $user);
        if ($remember) {
            session()->persist();
        }
        session()->flash('toast', 'Your account has been created.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/feed'));
    }

    public function logout(Request $request): void
    {
        $user = auth_user();
        if (is_array($user) && isset($user['id'])) {
            $userModel = new User();
            $userModel->migrate();
            $userModel->markOffline((int) $user['id']);
        }

        session()->destroy();
        session()->flash('toast', 'You have been logged out.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/login'));
    }

    public function forgotPassword(Request $request): void
    {
        $this->view('auth.forgot-password', [
            'title' => 'Forgot Password | ChatApp',
            'errors' => session()->getFlash('errors', []),
            'resetLink' => session()->getFlash('reset_link'),
        ]);
    }

    public function sendResetLink(Request $request): void
    {
        $email = trim((string) $request->input('email', ''));
        $userModel = new User();
        $userModel->migrate();
        $resetModel = new PasswordReset();
        $resetModel->migrate();

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            session()->flash('errors', ['email' => 'Enter a valid email address.']);
            $this->redirect(url('/forgot-password'));
        }

        if ($userModel->findByEmail($email) === null) {
            session()->flash('errors', ['email' => 'No account was found for that email.']);
            $this->redirect(url('/forgot-password'));
        }

        $token = bin2hex(random_bytes(16));
        $resetModel->create($email, $token);
        $resetUrl = url('/reset-password?email=' . urlencode($email) . '&token=' . urlencode($token));

        $mailed = send_app_mail(
            $email,
            'Reset your LivingSpring password',
            "Use this link to reset your password (valid for a limited time):\n\n" . $resetUrl
        );

        if ($mailed) {
            session()->flash('toast', 'Password reset link sent to your email.');
        } else {
            session()->flash('reset_link', $resetUrl);
            session()->flash('toast', 'Password reset link generated (email not configured — copy the link below).');
        }
        session()->flash('toast_type', 'success');
        $this->redirect(url('/forgot-password'));
    }

    public function resetPassword(Request $request): void
    {
        $this->view('auth.reset-password', [
            'title' => 'Reset Password | ChatApp',
            'errors' => session()->getFlash('errors', []),
            'email' => (string) $request->input('email', ''),
            'token' => (string) $request->input('token', ''),
        ]);
    }

    public function updatePassword(Request $request): void
    {
        $email = trim((string) $request->input('email', ''));
        $token = trim((string) $request->input('token', ''));
        $password = (string) $request->input('password', '');
        $confirmPassword = (string) $request->input('password_confirmation', '');
        $errors = [];

        if ($password === '' || strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($confirmPassword !== $password) {
            $errors['password_confirmation'] = 'Password confirmation must match.';
        }

        $resetModel = new PasswordReset();
        $resetModel->migrate();

        if (!$resetModel->findValid($email, $token)) {
            $errors['token'] = 'This reset link is invalid or expired.';
        }

        if ($errors !== []) {
            session()->flash('errors', $errors);
            $this->redirect(url('/reset-password?email=' . urlencode($email) . '&token=' . urlencode($token)));
        }

        $userModel = new User();
        $userModel->migrate();
        $userModel->updatePasswordByEmail($email, $password);
        $resetModel->deleteByEmail($email);

        session()->flash('toast', 'Your password has been reset. You can log in now.');
        session()->flash('toast_type', 'success');
        $this->redirect(url('/login'));
    }
}
