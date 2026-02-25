<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\HomeRequestRepository;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;
use DateTimeImmutable;
use Throwable;

final class HomeRequestController
{
    public function __construct(
        private HomeRequestRepository $requests,
        private AssetRepository $assets,
        private StaffRepository $staff,
        private LookupRepository $lookups
    ) {
    }

    public function index(): void
    {
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        View::render('home_requests/index', [
            'title' => 'Pedido para Levar Equipamento',
            'currentRoute' => 'ti.home-requests',
            'requests' => $this->requests->list($departmentScope, $scopedStaffId),
            'assets' => $this->assets->list('', $departmentScope, $scopedStaffId),
            'staff' => $this->staff->all($departmentScope, $scopedStaffId),
            'statuses' => $this->lookups->statuses(),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
            'canApprove' => $this->canApprove(),
            'requesterStaffScopeId' => $scopedStaffId,
        ]);
    }

    public function store(array $input): void
    {
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        $assetId = (int) ($input['asset_id'] ?? 0);
        $requesterStaffId = $scopedStaffId ?? (int) ($input['requester_staff_id'] ?? 0);
        $reason = trim((string) ($input['reason'] ?? ''));

        $asset = $assetId > 0 ? $this->assets->findById($assetId, $departmentScope, $scopedStaffId) : null;
        $requester = $requesterStaffId > 0 ? $this->staff->findById($requesterStaffId, $departmentScope, $scopedStaffId) : null;
        if ($asset === null || $requester === null || $reason === '') {
            View::redirect('ti.home-requests&error=1');
        }

        $documentText = $this->buildDocument(
            (string) $requester['name'],
            (string) $asset['tag'],
            (string) ($asset['serial_number'] ?? ''),
            (string) ($asset['condition_state'] ?? '')
        );

        try {
            $this->requests->create([
                'asset_id' => $assetId,
                'requester_staff_id' => $requesterStaffId,
                'requester_name' => (string) $requester['name'],
                'reason' => $reason,
                'condition_out' => (string) ($asset['condition_state'] ?? ''),
                'document_text' => $documentText,
            ]);
        } catch (Throwable) {
            View::redirect('ti.home-requests&error=2');
        }

        View::redirect('ti.home-requests&ok=1');
    }

    public function approve(array $input): void
    {
        if (!$this->canApprove()) {
            View::redirect('ti.home-requests&error=3');
        }

        $id = (int) ($input['id'] ?? 0);
        $dueReturnDate = trim((string) ($input['due_return_date'] ?? ''));
        if ($id <= 0 || ($dueReturnDate !== '' && !$this->isValidDate($dueReturnDate))) {
            View::redirect('ti.home-requests&error=1');
        }

        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        if ($this->requests->findById($id, $departmentScope, $scopedStaffId) === null) {
            View::redirect('ti.home-requests&error=1');
        }

        try {
            $this->requests->approve($id, $this->currentUserName(), $dueReturnDate !== '' ? $dueReturnDate : null);
        } catch (Throwable) {
            View::redirect('ti.home-requests&error=2');
        }

        View::redirect('ti.home-requests&ok=2');
    }

    public function reject(array $input): void
    {
        if (!$this->canApprove()) {
            View::redirect('ti.home-requests&error=3');
        }

        $id = (int) ($input['id'] ?? 0);
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        if ($id <= 0 || $this->requests->findById($id, $departmentScope, $scopedStaffId) === null) {
            View::redirect('ti.home-requests&error=1');
        }

        try {
            $this->requests->reject($id, $this->currentUserName());
        } catch (Throwable) {
            View::redirect('ti.home-requests&error=2');
        }

        View::redirect('ti.home-requests&ok=3');
    }

    public function markReturned(array $input): void
    {
        if (!$this->canApprove()) {
            View::redirect('ti.home-requests&error=3');
        }

        $id = (int) ($input['id'] ?? 0);
        $conditionIn = trim((string) ($input['condition_in'] ?? ''));
        $staffScope = AccessControl::staffScope(Auth::user());
        $departmentScope = $staffScope !== null && $staffScope > 0 ? null : AccessControl::departmentScope(Auth::user());
        $scopedStaffId = $staffScope !== null && $staffScope > 0 ? $staffScope : null;
        if ($id <= 0 || $conditionIn === '' || $this->requests->findById($id, $departmentScope, $scopedStaffId) === null) {
            View::redirect('ti.home-requests&error=1');
        }

        try {
            $this->requests->markReturned($id, $conditionIn);
        } catch (Throwable) {
            View::redirect('ti.home-requests&error=2');
        }

        View::redirect('ti.home-requests&ok=4');
    }

    private function buildDocument(string $requesterName, string $tag, string $serial, string $condition): string
    {
        $today = (new DateTimeImmutable())->format('Y-m-d');
        $lines = [
            'TERMO DE RETIRADA DE EQUIPAMENTO',
            '',
            'Solicitante: ' . $requesterName,
            'Data da solicitacao: ' . $today,
            'TAG do equipamento: ' . $tag,
            'Serial: ' . ($serial !== '' ? $serial : 'Nao informado'),
            'Estado de saida: ' . ($condition !== '' ? $condition : 'Nao informado'),
            '',
            'Declaro responsabilidade pelo equipamento durante o periodo autorizado.',
            '',
            'Assinatura solicitante: ___________________________',
            'Assinatura responsavel TI: ________________________',
        ];

        return implode("\n", $lines);
    }

    private function isValidDate(string $date): bool
    {
        $date = trim($date);
        if ($date === '') {
            return false;
        }

        $value = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $value && $value->format('Y-m-d') === $date;
    }

    private function currentUserName(): string
    {
        $user = Auth::user();
        return (string) ($user['name'] ?? $user['username'] ?? 'Sistema');
    }

    private function canApprove(): bool
    {
        $user = Auth::user();
        if (AccessControl::isFullAccess($user)) {
            return true;
        }

        if (!$user || !isset($user['allowed_routes']) || !is_array($user['allowed_routes'])) {
            return false;
        }

        return in_array('ti.home-requests.approve', $user['allowed_routes'], true);
    }
}
