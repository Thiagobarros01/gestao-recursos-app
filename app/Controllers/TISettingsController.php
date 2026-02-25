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
        View::render('ti/settings', [
            'title' => 'Configuracoes TI',
            'currentRoute' => 'ti.settings',
            'categories' => $this->lookups->categories(),
            'contractTypes' => $this->lookups->contractTypes(),
            'statuses' => $this->lookups->statuses(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
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
}
