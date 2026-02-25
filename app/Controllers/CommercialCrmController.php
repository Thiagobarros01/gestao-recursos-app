<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\CommercialCrmRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use Throwable;

final class CommercialCrmController
{
    public function __construct(
        private CommercialCrmRepository $crm,
        private UserRepository $users
    ) {
    }

    public function index(): void
    {
        $user = Auth::user();
        $filters = $this->readClientFilters($_GET);
        $settings = $this->crm->settings();
        $followupDays = (int) ($settings['followup_after_days'] ?? 30);

        View::render('commercial/crm', [
            'title' => 'CRM Comercial',
            'currentRoute' => 'commercial.crm',
            'clients' => $this->crm->clientsForUser($user, $filters),
            'clientOptions' => $this->crm->clientOptionsForUser($user),
            'sales' => $this->crm->salesForUser($user),
            'followupClients' => $this->crm->followupClientsForUser($user, $followupDays),
            'users' => $this->users->all(),
            'settings' => $settings,
            'filters' => $filters,
            'canManageSettings' => $this->canManageSettings($user),
            'canSeeAll' => $this->canSeeAll($user),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function storeClient(array $input): void
    {
        $user = Auth::user();
        $clientName = trim((string) ($input['client_name'] ?? ''));
        if ($clientName === '') {
            View::redirect('commercial.crm&error=1');
        }

        $ownerUserId = (int) ($input['owner_user_id'] ?? 0);
        if ($ownerUserId <= 0 || !$this->canSeeAll($user)) {
            $ownerUserId = (int) ($user['id'] ?? 0);
        }
        $status = trim((string) ($input['status'] ?? 'ativo'));
        if (!in_array($status, ['ativo', 'prospect', 'inativo'], true)) {
            $status = 'ativo';
        }

        try {
            $ok = $this->crm->createClient([
                'owner_user_id' => $ownerUserId,
                'client_name' => $clientName,
                'company_name' => trim((string) ($input['company_name'] ?? '')),
                'phone' => trim((string) ($input['phone'] ?? '')),
                'whatsapp' => trim((string) ($input['whatsapp'] ?? '')),
                'email' => trim((string) ($input['email'] ?? '')),
                'status' => $status,
                'notes' => trim((string) ($input['notes'] ?? '')),
            ]);
            if (!$ok) {
                View::redirect('commercial.crm&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm&error=2');
        }

        View::redirect('commercial.crm&ok=1');
    }

    public function storeSale(array $input): void
    {
        $user = Auth::user();
        $clientId = (int) ($input['client_id'] ?? 0);
        $saleDate = trim((string) ($input['sale_date'] ?? ''));
        $amountRaw = trim((string) ($input['amount'] ?? '0'));
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($clientId <= 0 || !$this->isValidDate($saleDate) || !$this->isValidMoney($amountRaw)) {
            View::redirect('commercial.crm&error=1');
        }
        if (!$this->crm->canUseClient($clientId, $user)) {
            View::redirect('commercial.crm&error=3');
        }

        $amount = (float) str_replace(',', '.', $amountRaw);
        try {
            $ok = $this->crm->createSale($clientId, (int) ($user['id'] ?? 0), $saleDate, $amount, $notes);
            if (!$ok) {
                View::redirect('commercial.crm&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm&error=2');
        }

        View::redirect('commercial.crm&ok=2');
    }

    public function updateSettings(array $input): void
    {
        $user = Auth::user();
        if (!$this->canManageSettings($user)) {
            View::redirect('commercial.crm&error=3');
        }

        $followupAfterDays = (int) ($input['followup_after_days'] ?? 30);
        if ($followupAfterDays <= 0 || $followupAfterDays > 3650) {
            View::redirect('commercial.crm&error=1');
        }

        try {
            $ok = $this->crm->updateSettings($followupAfterDays, (int) ($user['id'] ?? 0));
            if (!$ok) {
                View::redirect('commercial.crm&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm&error=2');
        }

        View::redirect('commercial.crm&ok=3');
    }

    private function readClientFilters(array $source): array
    {
        $status = trim((string) ($source['status_filter'] ?? ''));
        if (!in_array($status, ['ativo', 'inativo', 'prospect'], true)) {
            $status = '';
        }

        return [
            'q' => trim((string) ($source['q'] ?? '')),
            'status' => $status,
            'owner_user_id' => (int) ($source['owner_user_id'] ?? 0),
        ];
    }

    private function canManageSettings(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $role = AccessControl::normalizeRole($user['role'] ?? null);
        return in_array($role, ['admin', 'ti', 'gestor'], true);
    }

    private function canSeeAll(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (AccessControl::isFullAccess($user)) {
            return true;
        }
        return AccessControl::normalizeRole($user['role'] ?? null) === 'gestor';
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    private function isValidMoney(string $value): bool
    {
        $normalized = str_replace(',', '.', $value);
        return is_numeric($normalized) && (float) $normalized >= 0;
    }
}
