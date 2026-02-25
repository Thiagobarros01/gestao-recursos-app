<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\HomeRequestRepository;

final class ContractController
{
    public function __construct(
        private AssetRepository $assets,
        private HomeRequestRepository $homeRequests
    ) {
    }

    public function index(): void
    {
        $search = trim($_GET['q'] ?? '');
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;

        View::render('contracts/index', [
            'title' => 'Contratos e Termos',
            'currentRoute' => 'ti.contracts',
            'search' => $search,
            'assets' => $this->assets->list($search, $departmentScope, $scopedStaffId),
            'requests' => $this->homeRequests->list($departmentScope, $scopedStaffId),
        ]);
    }
}
