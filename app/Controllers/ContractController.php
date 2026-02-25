<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\HomeRequestRepository;
use App\Repositories\LookupRepository;
use DateTimeImmutable;
use Throwable;

final class ContractController
{
    public function __construct(
        private AssetRepository $assets,
        private HomeRequestRepository $homeRequests,
        private LookupRepository $lookups
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
            'contractTypes' => $this->lookups->contractTypes(),
            'canManageContracts' => $this->canManageContracts(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function update(array $input): void
    {
        if (!$this->canManageContracts()) {
            View::redirect('ti.contracts&error=3');
        }

        $id = (int) ($input['id'] ?? 0);
        $contractTypeId = (int) ($input['contract_type_id'] ?? 0);
        $purchaseDate = trim((string) ($input['purchase_date'] ?? ''));
        $warrantyUntil = trim((string) ($input['warranty_until'] ?? ''));
        $contractUntil = trim((string) ($input['contract_until'] ?? ''));
        $documentPath = trim((string) ($input['document_path'] ?? ''));

        if ($id <= 0 || ($contractTypeId > 0 && $this->lookups->contractTypeNameById($contractTypeId) === null)) {
            View::redirect('ti.contracts&error=1');
        }

        if (!$this->isValidDateOrEmpty($purchaseDate) || !$this->isValidDateOrEmpty($warrantyUntil) || !$this->isValidDateOrEmpty($contractUntil)) {
            View::redirect('ti.contracts&error=1');
        }

        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        $asset = $this->assets->findById($id, $departmentScope, $scopedStaffId);
        if ($asset === null) {
            View::redirect('ti.contracts&error=1');
        }

        try {
            $ok = $this->assets->updateContractData($id, [
                'contract_type_id' => $contractTypeId > 0 ? (string) $contractTypeId : '',
                'purchase_date' => $purchaseDate,
                'warranty_until' => $warrantyUntil,
                'contract_until' => $contractUntil,
                'document_path' => $documentPath,
            ]);
            if (!$ok) {
                View::redirect('ti.contracts&error=2');
            }
        } catch (Throwable) {
            View::redirect('ti.contracts&error=2');
        }

        View::redirect('ti.contracts&ok=1');
    }

    private function canManageContracts(): bool
    {
        $user = Auth::user();
        if (AccessControl::isFullAccess($user)) {
            return true;
        }

        return AccessControl::canAccessRoute('ti.contracts.update', $user);
    }

    private function isValidDateOrEmpty(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return true;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
