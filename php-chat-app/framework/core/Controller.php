<?php
declare(strict_types=1);

namespace Framework\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require view_path($view);
    }

    protected function redirect(string $path): void
    {
        (new Response())->redirect($path);
    }
}
