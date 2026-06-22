<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Core\Request;
use Framework\Core\Response;

class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!is_admin()) {
            (new Response())->redirect(url('/admin/auth'));
        }
    }
}
