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
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        $editId = (int) ($_GET['edit'] ?? 0);
        $editingStaff = $editId > 0 ? $this->staff->findById($editId, $departmentScope, $scopedStaffId) : null;

        View::render('staff/index', [
            'title' => 'Colaboradores TI',
            'currentRoute' => 'ti.staff',
            'staff' => $this->staff->all($departmentScope, $scopedStaffId),
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
        $staffScope = AccessControl::staffScope(Auth::user());
        if ($staffScope !== null && $staffScope > 0) {
            View::redirect('ti.staff&error=1');
        }

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
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;

        if ($id <= 0 || $name === '' || $department === '' || $this->staff->findById($id, $departmentScope, $scopedStaffId) === null) {
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
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        if ($id <= 0 || $this->staff->findById($id, $departmentScope, $scopedStaffId) === null) {
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
