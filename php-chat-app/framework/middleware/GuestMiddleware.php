<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Core\Request;
use Framework\Core\Response;

class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (auth_user() !== null) {
            (new Response())->redirect(url('/profile'));
        }
    }
}
