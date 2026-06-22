<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use App\Models\User;
use Framework\Core\Controller;
use Framework\Core\Request;

class SearchController extends Controller
{
    public function index(Request $request): void
    {
        $user = auth_user();
        if ($user === null) {
            $this->redirect(url('/login'));
        }

        $term = trim((string) $request->input('q', ''));
        $messageModel = new Message();
        $messageModel->migrate();

        $results = [];
        if ($term !== '') {
            $results = $messageModel->search(
                $term,
                (int) $user['id'],
                (string) ($user['class_name'] ?? ''),
                has_admin_privileges(),
            );
        }

        $this->view('search.index', [
            'title' => 'Search | LivingSpring',
            'term' => $term,
            'results' => $results,
        ]);
    }
}
