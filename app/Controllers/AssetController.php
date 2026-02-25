<?php

declare(strict_types=1);

namespace App\Controllers;

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
        $editId = (int) ($_GET['edit'] ?? 0);
        $editingAsset = $editId > 0 ? $this->assets->findById($editId) : null;

        View::render('assets/index', [
            'title' => 'Ativos e Contratos TI',
            'currentRoute' => 'ti.assets',
            'assets' => $this->assets->list($search),
            'staff' => $this->staff->all(),
            'categories' => $this->lookups->categories(),
            'contractTypes' => $this->lookups->contractTypes(),
            'statuses' => $this->lookups->statuses(),
            'search' => $search,
            'error' => $_GET['error'] ?? null,
            'success' => $_GET['ok'] ?? null,
            'editingAsset' => $editingAsset,
        ]);
    }

    public function store(array $input): void
    {
        $payload = $this->preparePayload($input);
        if ($payload === null) {
            View::redirect('ti.assets&error=1');
        }

        try {
            $this->assets->create($payload);
        } catch (Throwable $e) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=1');
    }

    public function update(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->assets->findById($id) === null) {
            View::redirect('ti.assets&error=3');
        }

        $payload = $this->preparePayload($input);
        if ($payload === null) {
            View::redirect('ti.assets&error=1');
        }

        try {
            $this->assets->update($id, $payload);
        } catch (Throwable $e) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=2');
    }

    public function delete(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            View::redirect('ti.assets&error=3');
        }

        try {
            $this->assets->delete($id);
        } catch (Throwable $e) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=3');
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

        $categoryName = $this->lookups->categoryNameById($categoryId) ?? '';
        $statusName = $this->lookups->statusNameById($statusId) ?? '';
        $contractTypeName = $this->lookups->contractTypeNameById($contractTypeId) ?? '';
        if ($categoryName === '' || $statusName === '' || $contractTypeName === '') {
            return null;
        }

        if ($staffId > 0 && $this->staff->findById($staffId) === null) {
            return null;
        }

        return [
            'category_id' => (string) $categoryId,
            'contract_type_id' => (string) $contractTypeId,
            'status_id' => (string) $statusId,
            'category_name' => $categoryName,
            'status_name' => $statusName,
            'tag' => trim((string) $input['tag']),
            'serial_number' => trim((string) ($input['serial_number'] ?? '')),
            'observation' => trim((string) ($input['observation'] ?? '')),
            'document_path' => trim((string) ($input['document_path'] ?? '')),
            'staff_id' => $staffId > 0 ? (string) $staffId : '',
        ];
    }
}
