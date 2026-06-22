<?php
declare(strict_types=1);

namespace App\Controllers;

use Framework\Core\Controller;
use Framework\Core\Request;

class LandingController extends Controller
{
    public function index(Request $request): void
    {
        if (auth_user() !== null) {
            $this->redirect(url('/feed'));
        }

        $this->view('landing', [
            'title' => 'Welcome | LivingSpring',
        ]);
    }
}
