<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\StaffRepository;

final class StaffController
{
    public function __construct(private StaffRepository $staff)
    {
    }

    public function index(): void
    {
        View::render('staff/index', [
            'title' => 'Colaboradores TI',
            'currentRoute' => 'ti.staff',
            'staff' => $this->staff->all(),
            'success' => $_GET['ok'] ?? null,
        ]);
    }

    public function store(array $input): void
    {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $department = trim($input['department'] ?? '');

        if ($name === '') {
            View::redirect('ti.staff');
        }

        $this->staff->create($name, $email !== '' ? $email : null, $department !== '' ? $department : null);
        View::redirect('ti.staff&ok=1');
    }
}
