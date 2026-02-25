<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\LookupRepository;
use Throwable;

final class TISettingsController
{
    public function __construct(private LookupRepository $lookups)
    {
    }

    public function index(): void
    {
        $categoryEditId = (int) ($_GET['category_edit'] ?? 0);
        $contractTypeEditId = (int) ($_GET['contract_edit'] ?? 0);
        $statusEditId = (int) ($_GET['status_edit'] ?? 0);

        View::render('ti/settings', [
            'title' => 'Configuracoes TI',
            'currentRoute' => 'ti.settings',
            'categories' => $this->lookups->categories(),
            'contractTypes' => $this->lookups->contractTypes(),
            'statuses' => $this->lookups->statuses(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
            'editingCategory' => $categoryEditId > 0 ? $this->lookups->findCategoryById($categoryEditId) : null,
            'editingContractType' => $contractTypeEditId > 0 ? $this->lookups->findContractTypeById($contractTypeEditId) : null,
            'editingStatus' => $statusEditId > 0 ? $this->lookups->findStatusById($statusEditId) : null,
        ]);
    }

    public function storeCategory(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->createCategory($name);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=1');
    }

    public function storeContractType(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->createContractType($name);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=1');
    }

    public function storeStatus(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->createStatus($name);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=1');
    }

    public function updateCategory(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findCategoryById($id) === null) {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->updateCategory($id, $name);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=2');
    }

    public function deleteCategory(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findCategoryById($id) === null) {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->deleteCategory($id);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=3');
    }

    public function updateContractType(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findContractTypeById($id) === null) {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->updateContractType($id, $name);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=2');
    }

    public function deleteContractType(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findContractTypeById($id) === null) {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->deleteContractType($id);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=3');
    }

    public function updateStatus(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findStatusById($id) === null) {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->updateStatus($id, $name);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=2');
    }

    public function deleteStatus(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findStatusById($id) === null) {
            View::redirect('ti.settings&error=1');
        }

        try {
            $this->lookups->deleteStatus($id);
        } catch (Throwable $e) {
            View::redirect('ti.settings&error=2');
        }

        View::redirect('ti.settings&ok=3');
    }
}
