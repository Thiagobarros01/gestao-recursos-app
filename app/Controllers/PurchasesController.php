<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AccessControl;
use App\Core\View;
use App\Repositories\PurchaseRepository;
use Throwable;

final class PurchasesController
{
    private const PRIORITIES = ['urgente', 'alta', 'normal', 'baixa'];
    private const SHORTAGE_STATUSES = ['pending', 'accepted', 'resolved', 'closed'];

    public function __construct(private PurchaseRepository $purchases)
    {
    }

    public function manage(): void
    {
        $filters = $this->readShortageFilters($_GET);
        if ((int) ($filters['mine'] ?? 0) === 1) {
            $filters['assigned_to_user_id'] = (int) (Auth::user()['id'] ?? 0);
        }

        View::render('purchases/manage', [
            'title' => 'Gerenciamento de Compras',
            'currentRoute' => 'purchases.manage',
            'products' => $this->purchases->products(),
            'alerts' => $this->purchases->shortageAlerts($filters),
            'filters' => $filters,
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function seller(): void
    {
        $currentUser = Auth::user();
        if (!(bool) ($currentUser['is_seller'] ?? false) && !AccessControl::isFullAccess($currentUser)) {
            View::redirect('areas');
        }
        $userId = (int) ($currentUser['id'] ?? 0);

        View::render('purchases/seller', [
            'title' => 'Modulo Vendedor',
            'currentRoute' => 'commercial.seller',
            'alerts' => $this->purchases->shortageAlerts([], $userId),
            'tiRequests' => $this->purchases->operatorTiRequests($userId),
            'priorities' => self::PRIORITIES,
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function storeProduct(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $sku = trim((string) ($input['sku'] ?? ''));
        $stockQty = (int) ($input['stock_qty'] ?? 0);
        $minQty = (int) ($input['min_qty'] ?? 0);
        if ($name === '') {
            View::redirect('purchases.manage&error=1');
        }

        try {
            $ok = $this->purchases->createProduct($name, $sku, $stockQty, $minQty);
            if (!$ok) {
                View::redirect('purchases.manage&error=2');
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2');
        }

        View::redirect('purchases.manage&ok=1');
    }

    public function updateProductStock(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $stockQty = (int) ($input['stock_qty'] ?? 0);
        $minQty = (int) ($input['min_qty'] ?? 0);
        if ($id <= 0) {
            View::redirect('purchases.manage&error=1');
        }

        try {
            $ok = $this->purchases->updateProductStock($id, $stockQty, $minQty);
            if (!$ok) {
                View::redirect('purchases.manage&error=2');
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2');
        }

        View::redirect('purchases.manage&ok=2');
    }

    public function storeShortage(array $input): void
    {
        $user = Auth::user();
        if (!(bool) ($user['is_seller'] ?? false) && !AccessControl::isFullAccess($user)) {
            View::redirect('areas');
        }
        $productCode = strtoupper(trim((string) ($input['product_code'] ?? '')));
        $productName = trim((string) ($input['product_name'] ?? ''));
        $details = trim((string) ($input['details'] ?? ''));
        $priority = strtolower(trim((string) ($input['priority'] ?? 'alta')));
        if ($productCode === '') {
            View::redirect('commercial.seller&error=1');
        }
        if (!in_array($priority, self::PRIORITIES, true)) {
            $priority = 'alta';
        }

        try {
            $result = $this->purchases->createShortageAlert(
                $productCode,
                $details,
                $priority,
                (int) ($user['id'] ?? 0),
                $productName
            );
            if ($result === 'already_open') {
                View::redirect('commercial.seller&ok=3');
            }
            if ($result !== 'created') {
                View::redirect('commercial.seller&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.seller&error=2');
        }

        View::redirect('commercial.seller&ok=1');
    }

    public function acceptShortage(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            View::redirect('purchases.manage&error=1');
        }

        try {
            $ok = $this->purchases->acceptShortageAlert($id, (int) (Auth::user()['id'] ?? 0));
            if (!$ok) {
                View::redirect('purchases.manage&error=2');
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2');
        }

        View::redirect('purchases.manage&ok=3' . $this->buildManageRedirectQuery($input));
    }

    public function resolveShortage(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            View::redirect('purchases.manage&error=1');
        }

        $note = trim((string) ($input['resolution_note'] ?? ''));
        try {
            $ok = $this->purchases->resolveShortageAlert($id, (int) (Auth::user()['id'] ?? 0), $note);
            if (!$ok) {
                View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
        }

        View::redirect('purchases.manage&ok=4' . $this->buildManageRedirectQuery($input));
    }

    public function closeShortage(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            View::redirect('purchases.manage&error=1');
        }

        $note = trim((string) ($input['resolution_note'] ?? ''));
        try {
            $ok = $this->purchases->closeShortageAlert($id, (int) (Auth::user()['id'] ?? 0), $note);
            if (!$ok) {
                View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
        }

        View::redirect('purchases.manage&ok=5' . $this->buildManageRedirectQuery($input));
    }

    public function resolveAllShortages(array $input): void
    {
        $filters = $this->readShortageFilters($input);
        $note = trim((string) ($input['resolution_note'] ?? ''));

        try {
            $ok = $this->purchases->resolveAllOpenShortages((int) (Auth::user()['id'] ?? 0), $note, $filters);
            if (!$ok) {
                View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
        }

        View::redirect('purchases.manage&ok=6' . $this->buildManageRedirectQuery($input));
    }

    public function closeAllShortages(array $input): void
    {
        $filters = $this->readShortageFilters($input);
        $note = trim((string) ($input['resolution_note'] ?? ''));

        try {
            $ok = $this->purchases->closeAllOpenShortages((int) (Auth::user()['id'] ?? 0), $note, $filters);
            if (!$ok) {
                View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
            }
        } catch (Throwable) {
            View::redirect('purchases.manage&error=2' . $this->buildManageRedirectQuery($input));
        }

        View::redirect('purchases.manage&ok=7' . $this->buildManageRedirectQuery($input));
    }

    public function storeTiRequest(array $input): void
    {
        $user = Auth::user();
        if (!(bool) ($user['is_seller'] ?? false) && !AccessControl::isFullAccess($user)) {
            View::redirect('areas');
        }
        $reason = trim((string) ($input['reason'] ?? ''));
        $details = trim((string) ($input['details'] ?? ''));
        if ($reason === '') {
            View::redirect('commercial.seller&error=1');
        }

        try {
            $ok = $this->purchases->createOperatorTiRequest(
                (int) ($user['id'] ?? 0),
                $reason,
                $details
            );
            if (!$ok) {
                View::redirect('commercial.seller&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.seller&error=2');
        }

        View::redirect('commercial.seller&ok=2');
    }

    private function readShortageFilters(array $source): array
    {
        $status = trim((string) ($source['status_filter'] ?? ''));
        if (!in_array($status, self::SHORTAGE_STATUSES, true)) {
            $status = '';
        }

        return [
            'status' => $status,
            'date_from' => trim((string) ($source['date_from'] ?? '')),
            'date_to' => trim((string) ($source['date_to'] ?? '')),
            'month' => trim((string) ($source['month'] ?? '')),
            'mine' => (int) ((string) ($source['mine'] ?? '0') === '1'),
        ];
    }

    private function buildManageRedirectQuery(array $source): string
    {
        $filters = $this->readShortageFilters($source);
        $query = [];
        foreach ($filters as $key => $value) {
            if ($key === 'assigned_to_user_id') {
                continue;
            }
            if ($key === 'mine' && (int) $value !== 1) {
                continue;
            }
            if ($value === '') {
                continue;
            }
            $query[] = $key . '=' . urlencode($value);
        }

        return empty($query) ? '' : '&' . implode('&', $query);
    }
}
