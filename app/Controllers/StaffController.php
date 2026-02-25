<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;
use Throwable;

final class StaffController
{
    public function __construct(
        private StaffRepository $staff,
        private LookupRepository $lookups
    ) {
    }

    public function index(): void
    {
        $departmentScope = AccessControl::departmentScope(Auth::user());
        $editId = (int) ($_GET['edit'] ?? 0);
        $editingStaff = $editId > 0 ? $this->staff->findById($editId, $departmentScope) : null;

        View::render('staff/index', [
            'title' => 'Colaboradores TI',
            'currentRoute' => 'ti.staff',
            'staff' => $this->staff->all($departmentScope),
            'departments' => $this->lookups->departments(),
            'departmentScope' => $departmentScope,
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
            'editingStaff' => $editingStaff,
        ]);
    }

    public function store(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $department = trim((string) ($input['department'] ?? ''));
        $department = $this->effectiveDepartment($department);

        if ($name === '' || $department === '') {
            View::redirect('ti.staff&error=1');
        }

        try {
            $this->staff->create($name, $email !== '' ? $email : null, $department);
        } catch (Throwable) {
            View::redirect('ti.staff&error=2');
        }

        View::redirect('ti.staff&ok=1');
    }

    public function update(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $department = trim((string) ($input['department'] ?? ''));
        $department = $this->effectiveDepartment($department);
        $departmentScope = AccessControl::departmentScope(Auth::user());

        if ($id <= 0 || $name === '' || $department === '' || $this->staff->findById($id, $departmentScope) === null) {
            View::redirect('ti.staff&error=1');
        }

        try {
            $this->staff->update($id, $name, $email !== '' ? $email : null, $department);
        } catch (Throwable) {
            View::redirect('ti.staff&error=2');
        }

        View::redirect('ti.staff&ok=2');
    }

    public function delete(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $departmentScope = AccessControl::departmentScope(Auth::user());
        if ($id <= 0 || $this->staff->findById($id, $departmentScope) === null) {
            View::redirect('ti.staff&error=1');
        }

        try {
            $this->staff->delete($id);
        } catch (Throwable) {
            View::redirect('ti.staff&error=2');
        }

        View::redirect('ti.staff&ok=3');
    }

    private function effectiveDepartment(string $requested): string
    {
        $user = Auth::user();
        if (AccessControl::isFullAccess($user)) {
            return $requested;
        }

        return trim((string) ($user['department_name'] ?? ''));
    }
}
