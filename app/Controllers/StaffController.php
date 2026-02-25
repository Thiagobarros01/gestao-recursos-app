<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\StaffRepository;
use Throwable;

final class StaffController
{
    public function __construct(private StaffRepository $staff)
    {
    }

    public function index(): void
    {
        $editId = (int) ($_GET['edit'] ?? 0);
        $editingStaff = $editId > 0 ? $this->staff->findById($editId) : null;

        View::render('staff/index', [
            'title' => 'Colaboradores TI',
            'currentRoute' => 'ti.staff',
            'staff' => $this->staff->all(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
            'editingStaff' => $editingStaff,
        ]);
    }

    public function store(array $input): void
    {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $department = trim($input['department'] ?? '');

        if ($name === '') {
            View::redirect('ti.staff&error=1');
        }

        try {
            $this->staff->create($name, $email !== '' ? $email : null, $department !== '' ? $department : null);
        } catch (Throwable $e) {
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

        if ($id <= 0 || $name === '' || $this->staff->findById($id) === null) {
            View::redirect('ti.staff&error=1');
        }

        try {
            $this->staff->update($id, $name, $email !== '' ? $email : null, $department !== '' ? $department : null);
        } catch (Throwable $e) {
            View::redirect('ti.staff&error=2');
        }

        View::redirect('ti.staff&ok=2');
    }

    public function delete(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || $this->staff->findById($id) === null) {
            View::redirect('ti.staff&error=1');
        }

        try {
            $this->staff->delete($id);
        } catch (Throwable $e) {
            View::redirect('ti.staff&error=2');
        }

        View::redirect('ti.staff&ok=3');
    }
}
