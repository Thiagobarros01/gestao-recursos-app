<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\HomeRequestRepository;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;

final class DashboardController
{
    public function __construct(
        private AssetRepository $assets,
        private StaffRepository $staff,
        private LookupRepository $lookups,
        private HomeRequestRepository $homeRequests
    ) {
    }

    public function index(): void
    {
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $staffList = $this->staff->all($departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null);

        View::render('dashboard', [
            'title' => 'Gerenciamento da TI',
            'currentRoute' => 'ti.dashboard',
            'totalAssets' => $this->assets->countAll($departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'totalStaff' => count($staffList),
            'emUso' => $this->assets->countByStatus('Em uso', $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'devolvido' => $this->assets->countByStatus('Devolvido', $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'perda' => $this->assets->countByStatus('Perda', $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'roubo' => $this->assets->countByStatus('Roubo', $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'homeRequestsPending' => $this->homeRequests->countByStatus('pending', $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'homeRequestsApproved' => $this->homeRequests->countByStatus('approved', $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'totalCategories' => count($this->lookups->categories()),
            'totalContracts' => count($this->lookups->contractTypes()),
        ]);
    }
}
