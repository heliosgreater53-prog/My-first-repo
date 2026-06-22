<?php
declare(strict_types=1);

namespace Framework\Middleware;

use App\Models\User;
use Framework\Core\Request;
use Framework\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $user = auth_user();

        if ($user === null) {
            session()->flash('errors', [
                'auth' => 'Please log in to continue.',
            ]);

            (new Response())->redirect(url('/login'));
        }

        if (isset($user['id'])) {
            $userModel = new User();
            $userModel->migrate();
            $userModel->updateLastActivity((int) $user['id']);
        }
    }
}
