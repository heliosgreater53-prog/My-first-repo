<?php
declare(strict_types=1);

namespace Framework\Core;

class App
{
    private Router $router;
    private Session $session;

    public function __construct(
        Router $router,
        Session $session,
    ) {
        $this->router = $router;
        $this->session = $session;
    }

    public function boot(): void
    {
        $this->session->start();
        $this->router->dispatch(new Request());
    }
}
