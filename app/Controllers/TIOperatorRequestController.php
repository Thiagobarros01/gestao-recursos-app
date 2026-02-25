<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Repositories\PurchaseRepository;
use Throwable;

final class TIOperatorRequestController
{
    public function __construct(private PurchaseRepository $purchases)
    {
    }

    public function index(): void
    {
        View::render('ti/operator_requests', [
            'title' => 'Pedidos Operadores',
            'currentRoute' => 'ti.operator-requests',
            'requests' => $this->purchases->operatorTiRequests(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function approve(array $input): void
    {
        $this->review($input, 'approved');
    }

    public function reject(array $input): void
    {
        $this->review($input, 'rejected');
    }

    private function review(array $input, string $status): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            View::redirect('ti.operator-requests&error=1');
        }

        try {
            $ok = $this->purchases->reviewOperatorTiRequest($id, (int) (Auth::user()['id'] ?? 0), $status);
            if (!$ok) {
                View::redirect('ti.operator-requests&error=2');
            }
        } catch (Throwable) {
            View::redirect('ti.operator-requests&error=2');
        }

        View::redirect('ti.operator-requests&ok=1');
    }
}
