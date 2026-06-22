<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Core\Request;
use Framework\Core\Response;

class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if ($request->method() !== 'POST') {
            return;
        }

        $token = (string) $request->input('_token', '');

        if (!hash_equals(csrf_token(), $token)) {
            session()->flash('toast', 'Your session token expired. Please try again.');
            session()->flash('toast_type', 'error');

            (new Response())->redirect(url('/login'));
        }
    }
}
