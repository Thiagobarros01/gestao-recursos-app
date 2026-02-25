<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;

final class DashboardController
{
    public function __construct(
        private AssetRepository $assets,
        private StaffRepository $staff,
        private LookupRepository $lookups
    ) {
    }

    public function index(): void
    {
        View::render('dashboard', [
            'title' => 'Gerenciamento da TI',
            'currentRoute' => 'ti.dashboard',
            'totalAssets' => $this->assets->countAll(),
            'totalStaff' => count($this->staff->all()),
            'emUso' => $this->assets->countByStatus('Em uso'),
            'devolvido' => $this->assets->countByStatus('Devolvido'),
            'perda' => $this->assets->countByStatus('Perda'),
            'roubo' => $this->assets->countByStatus('Roubo'),
            'totalCategories' => count($this->lookups->categories()),
            'totalContracts' => count($this->lookups->contractTypes()),
        ]);
    }
}
