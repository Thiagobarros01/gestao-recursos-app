<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
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
        $departmentScope = AccessControl::departmentScope(Auth::user());
        $staffList = $this->staff->all($departmentScope);

        View::render('dashboard', [
            'title' => 'Gerenciamento da TI',
            'currentRoute' => 'ti.dashboard',
            'totalAssets' => $this->assets->countAll($departmentScope),
            'totalStaff' => count($staffList),
            'emUso' => $this->assets->countByStatus('Em uso', $departmentScope),
            'devolvido' => $this->assets->countByStatus('Devolvido', $departmentScope),
            'perda' => $this->assets->countByStatus('Perda', $departmentScope),
            'roubo' => $this->assets->countByStatus('Roubo', $departmentScope),
            'totalCategories' => count($this->lookups->categories()),
            'totalContracts' => count($this->lookups->contractTypes()),
        ]);
    }
}
