<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\View;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;
use App\Repositories\UserRepository;
use Throwable;

final class TISettingsController
{
    public function __construct(
        private LookupRepository $lookups,
        private UserRepository $users,
        private StaffRepository $staff
    ) {
    }

    public function index(): void
    {
        $categoryEditId = (int) ($_GET['category_edit'] ?? 0);
        $contractTypeEditId = (int) ($_GET['contract_edit'] ?? 0);
        $statusEditId = (int) ($_GET['status_edit'] ?? 0);
        $departmentEditId = (int) ($_GET['department_edit'] ?? 0);
        $userEditId = (int) ($_GET['user_edit'] ?? 0);

        View::render('ti/settings', [
            'title' => 'Configuracoes do Sistema',
            'currentRoute' => 'settings',
            'categories' => $this->lookups->categories(),
            'contractTypes' => $this->lookups->contractTypes(),
            'statuses' => $this->lookups->statuses(),
            'departments' => $this->lookups->departments(),
            'staffMembers' => $this->staff->all(),
            'users' => $this->users->all(),
            'permissionGroups' => AccessControl::permissionGroups(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
            'editingCategory' => $categoryEditId > 0 ? $this->lookups->findCategoryById($categoryEditId) : null,
            'editingContractType' => $contractTypeEditId > 0 ? $this->lookups->findContractTypeById($contractTypeEditId) : null,
            'editingStatus' => $statusEditId > 0 ? $this->lookups->findStatusById($statusEditId) : null,
            'editingDepartment' => $departmentEditId > 0 ? $this->lookups->findDepartmentById($departmentEditId) : null,
            'editingUser' => $userEditId > 0 ? $this->users->findById($userEditId) : null,
        ]);
    }

    public function storeCategory(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->createCategory($name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=1');
    }

    public function storeContractType(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->createContractType($name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=1');
    }

    public function storeStatus(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->createStatus($name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=1');
    }

    public function updateCategory(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findCategoryById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->updateCategory($id, $name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=2');
    }

    public function deleteCategory(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findCategoryById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->deleteCategory($id);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=3');
    }

    public function updateContractType(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findContractTypeById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->updateContractType($id, $name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=2');
    }

    public function deleteContractType(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findContractTypeById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->deleteContractType($id);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=3');
    }

    public function updateStatus(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findStatusById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->updateStatus($id, $name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=2');
    }

    public function deleteStatus(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findStatusById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->deleteStatus($id);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=3');
    }

    public function storeDepartment(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->createDepartment($name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=1');
    }

    public function updateDepartment(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($id <= 0 || $name === '' || $this->lookups->findDepartmentById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->updateDepartment($id, $name);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=2');
    }

    public function deleteDepartment(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->lookups->findDepartmentById($id) === null) {
            View::redirect('settings&error=1');
        }

        try {
            $this->lookups->deleteDepartment($id);
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=3');
    }

    public function storeUser(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $role = AccessControl::normalizeRole($input['role'] ?? 'operador');
        $departmentId = (int) ($input['department_id'] ?? 0);
        $staffId = (int) ($input['staff_id'] ?? 0);
        $permissionGroups = $input['permission_groups'] ?? [];
        $isSeller = (int) ($input['is_seller'] ?? 0) === 1;

        if ($name === '' || $username === '' || $password === '') {
            View::redirect('settings&error=1');
        }

        if (!is_array($permissionGroups)) {
            $permissionGroups = [];
        }

        $routes = in_array($role, ['gestor', 'operador'], true) ? AccessControl::routesFromGroupKeys($permissionGroups) : [];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $departmentValue = $departmentId > 0 ? $departmentId : null;
        $staffValue = $staffId > 0 ? $staffId : null;

        $ok = $this->users->create(
            $name,
            $username,
            $passwordHash,
            $role,
            $departmentValue,
            $staffValue,
            $isSeller,
            $routes
        );

        if (!$ok) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=1');
    }

    public function updateUser(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $existing = $id > 0 ? $this->users->findById($id) : null;
        if ($existing === null) {
            View::redirect('settings&error=1');
        }

        $name = trim((string) ($input['name'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $role = AccessControl::normalizeRole($input['role'] ?? 'operador');
        $departmentId = (int) ($input['department_id'] ?? 0);
        $staffId = (int) ($input['staff_id'] ?? 0);
        $permissionGroups = $input['permission_groups'] ?? [];
        $isSeller = (int) ($input['is_seller'] ?? 0) === 1;

        if ($name === '' || $username === '') {
            View::redirect('settings&error=1');
        }
        if (!is_array($permissionGroups)) {
            $permissionGroups = [];
        }

        $routes = in_array($role, ['gestor', 'operador'], true) ? AccessControl::routesFromGroupKeys($permissionGroups) : [];
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
        $departmentValue = $departmentId > 0 ? $departmentId : null;
        $staffValue = $staffId > 0 ? $staffId : null;

        $ok = $this->users->update(
            $id,
            $name,
            $username,
            $passwordHash,
            $role,
            $departmentValue,
            $staffValue,
            $isSeller,
            $routes
        );

        if (!$ok) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=2');
    }
}
