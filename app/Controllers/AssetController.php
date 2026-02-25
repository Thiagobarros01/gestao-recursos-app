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
        ]);
    }

    public function store(array $input): void
    {
        $required = ['category_id', 'contract_type_id', 'status_id', 'tag'];
        foreach ($required as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                View::redirect('ti.assets&error=1');
            }
        }

        $categoryId = (int) trim((string) $input['category_id']);
        $statusId = (int) trim((string) $input['status_id']);
        $categoryName = $this->lookups->categoryNameById($categoryId) ?? '';
        $statusName = $this->lookups->statusNameById($statusId) ?? '';

        if ($categoryName === '' || $statusName === '') {
            View::redirect('ti.assets&error=1');
        }

        try {
            $this->assets->create([
                'category_id' => (string) $categoryId,
                'contract_type_id' => trim((string) $input['contract_type_id']),
                'status_id' => (string) $statusId,
                'category_name' => $categoryName,
                'status_name' => $statusName,
                'tag' => trim((string) $input['tag']),
                'serial_number' => trim((string) ($input['serial_number'] ?? '')),
                'observation' => trim((string) ($input['observation'] ?? '')),
                'document_path' => trim((string) ($input['document_path'] ?? '')),
                'staff_id' => (string) ($input['staff_id'] ?? ''),
            ]);
        } catch (Throwable $e) {
            View::redirect('ti.assets&error=2');
        }

        View::redirect('ti.assets&ok=1');
    }
}
