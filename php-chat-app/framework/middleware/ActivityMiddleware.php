<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Core\Request;
use Framework\Core\Response;
use App\Models\User;

class ActivityMiddleware
{
    public function handle(Request $request): void
    {
        $user = auth_user();

        if ($user !== null && isset($user['id'])) {
            $userModel = new User();
            $userModel->updateLastActivity((int) $user['id']);
        }
    }
}
