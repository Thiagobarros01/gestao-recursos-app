<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;
use Throwable;

final class AssetController
{
    public function __construct(
        private AssetRepository $assets,
        private StaffRepository $staff,
        private LookupRepository $lookups
    ) {
    }

    public function index(): void
    {
        $search = trim($_GET['q'] ?? '');
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $selectedDepartmentId = max(0, (int) ($_GET['department_id'] ?? 0));
        $selectedResponsibleId = max(0, (int) ($_GET['responsible_id'] ?? 0));
        if ($staffScope !== null && $staffScope > 0) {
            $selectedResponsibleId = $staffScope;
        }
        $canStore = AccessControl::canAccessRoute('ti.assets.store', Auth::user());
        $canUpdate = AccessControl::canAccessRoute('ti.assets.update', Auth::user());
        $canDelete = AccessControl::canAccessRoute('ti.assets.delete', Auth::user());
        $canTransfer = AccessControl::canAccessRoute('ti.assets.transfer', Auth::user());
        $editId = (int) ($_GET['edit'] ?? 0);
        $transferId = (int) ($_GET['transfer'] ?? 0);
        $editingAsset = $canUpdate && $editId > 0 ? $this->assets->findById($editId, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null) : null;
        $transferringAsset = $canTransfer && $transferId > 0 ? $this->assets->findById($transferId, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null) : null;
        $movements = $editingAsset ? $this->assets->movementsByAssetId((int) $editingAsset['id'], 20) : [];
        $transferMovements = $transferringAsset ? $this->assets->movementsByAssetId((int) $transferringAsset['id'], 20) : [];

        View::render('assets/index', [
            'title' => 'Ativos e Contratos TI',
            'currentRoute' => 'ti.assets',
            'assets' => $this->assets->list(
                $search,
                $departmentScope,
                $staffScope !== null && $staffScope > 0 ? $staffScope : null,
                $selectedDepartmentId > 0 ? $selectedDepartmentId : null,
                $selectedResponsibleId > 0 ? $selectedResponsibleId : null
            ),
            'staff' => $this->staff->all($departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null),
            'categories' => $this->lookups->categories(),
            'contractTypes' => $this->lookups->contractTypes(),
            'statuses' => $this->lookups->statuses(),
            'departments' => $this->lookups->departments(),
            'search' => $search,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedResponsibleId' => $selectedResponsibleId,
            'error' => $_GET['error'] ?? null,
            'success' => $_GET['ok'] ?? null,
            'editingAsset' => $editingAsset,
            'movements' => $movements,
            'transferringAsset' => $transferringAsset,
            'transferMovements' => $transferMovements,
            'canStore' => $canStore,
            'canUpdate' => $canUpdate,
            'canDelete' => $canDelete,
            'canTransfer' => $canTransfer,
        ]);
    }

    public function store(array $input): void
    {
        $payload = $this->preparePayload($input);
        if ($payload === null) {
            View::redirect('ti.assets&error=1');
        }

        try {
            $assetId = $this->assets->createAndGetId($payload);
            if ($assetId <= 0) {
                View::redirect('ti.assets&error=2');
            }

            $this->assets->addMovement([
                'asset_id' => $assetId,
                'movement_type' => 'create',
                'details' => 'Cadastro inicial do ativo.',
                'to_status' => $payload['status_name'],
                'to_staff' => $payload['staff_name'],
                'changed_by' => $this->currentUserName(),
            ]);
        } catch (Throwable) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=1');
    }

    public function update(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $before = $id > 0 ? $this->assets->findById($id, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null) : null;
        if ($before === null) {
            View::redirect('ti.assets&error=3');
        }

        $payload = $this->preparePayload($input);
        if ($payload === null) {
            View::redirect('ti.assets&error=1');
        }

        try {
            $this->assets->update($id, $payload);
            $movement = $this->buildMovementDetails($before, $payload, trim((string) ($input['movement_note'] ?? '')));
            if ($movement !== null) {
                $movement['asset_id'] = $id;
                $movement['changed_by'] = $this->currentUserName();
                $this->assets->addMovement($movement);
            }
        } catch (Throwable) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=2');
    }

    public function delete(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $existing = $id > 0 ? $this->assets->findById($id, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null) : null;
        if ($existing === null) {
            View::redirect('ti.assets&error=3');
        }

        try {
            $this->assets->delete($id);
        } catch (Throwable) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=3');
    }

    public function transfer(array $input): void
    {
        $assetId = (int) ($input['id'] ?? 0);
        $toStaffId = (int) ($input['to_staff_id'] ?? 0);
        $newStatusId = (int) ($input['status_id'] ?? 0);
        $reason = trim((string) ($input['reason'] ?? ''));
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());

        $asset = $assetId > 0 ? $this->assets->findById($assetId, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null) : null;
        if ($asset === null || $toStaffId <= 0 || $reason === '') {
            View::redirect('ti.assets&error=4');
        }

        $toStaff = $this->staff->findById($toStaffId, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null);
        if ($toStaff === null) {
            View::redirect('ti.assets&error=4');
        }

        $fromStaffName = (string) ($asset['staff_name'] ?? '');
        $toStaffName = (string) $toStaff['name'];
        $fromStatusName = (string) ($asset['status_name'] ?? $asset['status'] ?? '');
        $statusId = $newStatusId > 0 ? $newStatusId : (int) ($asset['status_id'] ?? 0);
        $statusName = $statusId > 0 ? ($this->lookups->statusNameById($statusId) ?? '') : $fromStatusName;
        if ($statusName === '') {
            View::redirect('ti.assets&error=4');
        }

        try {
            $this->assets->transfer($assetId, $toStaffId, $statusId > 0 ? $statusId : null, $statusName);
            $this->assets->addMovement([
                'asset_id' => $assetId,
                'movement_type' => 'transfer',
                'details' => $reason,
                'from_status' => $fromStatusName,
                'to_status' => $statusName,
                'from_staff' => $fromStaffName,
                'to_staff' => $toStaffName,
                'changed_by' => $this->currentUserName(),
            ]);
        } catch (Throwable) {
            View::redirect('ti.assets&error=5');
        }

        View::redirect('ti.assets&ok=4');
    }

    private function preparePayload(array $input): ?array
    {
        $required = ['category_id', 'contract_type_id', 'status_id', 'tag'];
        foreach ($required as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                return null;
            }
        }

        $categoryId = (int) trim((string) $input['category_id']);
        $contractTypeId = (int) trim((string) $input['contract_type_id']);
        $statusId = (int) trim((string) $input['status_id']);
        $staffId = (int) ($input['staff_id'] ?? 0);
        $departmentId = (int) ($input['department_id'] ?? 0);
        $ownershipType = trim((string) ($input['ownership_type'] ?? ''));
        $networkMode = strtolower(trim((string) ($input['network_mode'] ?? '')));
        $ipAddress = trim((string) ($input['ip_address'] ?? ''));

        if (!in_array($ownershipType, ['proprio', 'terceirizado'], true)) {
            return null;
        }

        if (!in_array($networkMode, ['dhcp', 'estatico', ''], true)) {
            return null;
        }

        if ($networkMode === 'dhcp') {
            $ipAddress = '';
        }

        if ($ipAddress !== '' && !$this->isValidIp($ipAddress)) {
            return null;
        }

        $purchaseDate = $this->normalizeDate((string) ($input['purchase_date'] ?? ''));
        $warrantyUntil = $this->normalizeDate((string) ($input['warranty_until'] ?? ''));
        $contractUntil = $this->normalizeDate((string) ($input['contract_until'] ?? ''));
        $returnedAt = $this->normalizeDate((string) ($input['returned_at'] ?? ''));

        if ($purchaseDate === null || $warrantyUntil === null || $contractUntil === null || $returnedAt === null) {
            return null;
        }

        $categoryName = $this->lookups->categoryNameById($categoryId) ?? '';
        $statusName = $this->lookups->statusNameById($statusId) ?? '';
        $contractTypeName = $this->lookups->contractTypeNameById($contractTypeId) ?? '';
        if ($categoryName === '' || $statusName === '' || $contractTypeName === '') {
            return null;
        }

        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $staffName = '';
        if ($staffId > 0) {
            $staffRow = $this->staff->findById($staffId, $departmentScope, $staffScope !== null && $staffScope > 0 ? $staffScope : null);
            if ($staffRow === null) {
                return null;
            }
            $staffName = (string) $staffRow['name'];
        }

        $departmentIdValue = '';
        if ($departmentId > 0) {
            $departmentName = $this->lookups->departmentNameById($departmentId);
            if ($departmentName === null) {
                return null;
            }
            if ($departmentScope !== null && $departmentScope !== '__none__' && $departmentScope !== $departmentName) {
                return null;
            }
            $departmentIdValue = (string) $departmentId;
        }

        return [
            'category_id' => (string) $categoryId,
            'contract_type_id' => (string) $contractTypeId,
            'status_id' => (string) $statusId,
            'category_name' => $categoryName,
            'status_name' => $statusName,
            'staff_name' => $staffName,
            'condition_state' => trim((string) ($input['condition_state'] ?? '')),
            'tag' => trim((string) $input['tag']),
            'serial_number' => trim((string) ($input['serial_number'] ?? '')),
            'observation' => trim((string) ($input['observation'] ?? '')),
            'document_path' => trim((string) ($input['document_path'] ?? '')),
            'staff_id' => $staffId > 0 ? (string) $staffId : '',
            'purchase_date' => $purchaseDate,
            'warranty_until' => $warrantyUntil,
            'contract_until' => $contractUntil,
            'returned_at' => $returnedAt,
            'ownership_type' => $ownershipType,
            'department_id' => $departmentIdValue,
            'network_mode' => $networkMode,
            'ip_address' => $ipAddress,
        ];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    private function buildMovementDetails(array $before, array $payload, string $note): ?array
    {
        $fromStatus = (string) ($before['status_name'] ?? $before['status'] ?? '');
        $toStatus = (string) $payload['status_name'];
        $fromStaff = (string) ($before['staff_name'] ?? '');
        $toStaff = (string) $payload['staff_name'];

        $changes = [];
        if ((string) $before['tag'] !== $payload['tag']) {
            $changes[] = 'TAG';
        }
        if ((string) ($before['serial_number'] ?? '') !== $payload['serial_number']) {
            $changes[] = 'Serial';
        }
        if ((string) ($before['document_path'] ?? '') !== $payload['document_path']) {
            $changes[] = 'Documento';
        }
        if ((string) ($before['condition_state'] ?? '') !== $payload['condition_state']) {
            $changes[] = 'Estado';
        }
        if ((string) ($before['purchase_date'] ?? '') !== $payload['purchase_date']) {
            $changes[] = 'Compra';
        }
        if ((string) ($before['warranty_until'] ?? '') !== $payload['warranty_until']) {
            $changes[] = 'Garantia';
        }
        if ((string) ($before['contract_until'] ?? '') !== $payload['contract_until']) {
            $changes[] = 'Contrato';
        }
        if ((string) ($before['returned_at'] ?? '') !== $payload['returned_at']) {
            $changes[] = 'Devolucao';
        }
        if ((string) ($before['observation'] ?? $before['notes'] ?? '') !== $payload['observation']) {
            $changes[] = 'Observacao';
        }
        if ((string) ($before['ownership_type'] ?? '') !== $payload['ownership_type']) {
            $changes[] = 'Propriedade';
        }
        if ((int) ($before['department_id'] ?? 0) !== (int) ($payload['department_id'] !== '' ? $payload['department_id'] : 0)) {
            $changes[] = 'Departamento';
        }
        if ((string) ($before['network_mode'] ?? '') !== $payload['network_mode']) {
            $changes[] = 'Rede';
        }
        if ((string) ($before['ip_address'] ?? '') !== $payload['ip_address']) {
            $changes[] = 'IP';
        }

        $statusChanged = $fromStatus !== $toStatus;
        $staffChanged = $fromStaff !== $toStaff;
        if (!$statusChanged && !$staffChanged && empty($changes) && $note === '') {
            return null;
        }

        $parts = [];
        if ($statusChanged) {
            $parts[] = 'Status atualizado';
        }
        if ($staffChanged) {
            $parts[] = 'Responsavel atualizado';
        }
        if (!empty($changes)) {
            $parts[] = 'Campos: ' . implode(', ', $changes);
        }
        if ($note !== '') {
            $parts[] = 'Motivo: ' . $note;
        }

        return [
            'movement_type' => ($statusChanged || $staffChanged) ? 'assignment' : 'update',
            'details' => implode(' | ', $parts),
            'from_status' => $statusChanged ? $fromStatus : '',
            'to_status' => $statusChanged ? $toStatus : '',
            'from_staff' => $staffChanged ? $fromStaff : '',
            'to_staff' => $staffChanged ? $toStaff : '',
        ];
    }

    private function currentUserName(): string
    {
        $user = Auth::user();
        return (string) ($user['name'] ?? $user['username'] ?? 'Sistema');
    }

    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
}
