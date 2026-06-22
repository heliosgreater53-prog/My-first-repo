<?php
declare(strict_types=1);

namespace Framework\Core;

class Response
{
    public function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
