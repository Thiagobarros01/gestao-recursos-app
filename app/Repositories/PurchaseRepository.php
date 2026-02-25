<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PurchaseRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function products(): array
    {
        return $this->pdo->query(
            'SELECT *,
                    CASE WHEN stock_qty <= min_qty THEN 1 ELSE 0 END AS is_shortage
             FROM purchase_products
             ORDER BY name ASC'
        )->fetchAll();
    }

    public function createProduct(string $name, string $sku, int $stockQty, int $minQty): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO purchase_products (name, sku, stock_qty, min_qty, updated_at)
             VALUES (:name, :sku, :stock_qty, :min_qty, CURRENT_TIMESTAMP)'
        );
        return $stmt->execute([
            ':name' => $name,
            ':sku' => $sku !== '' ? $sku : null,
            ':stock_qty' => max(0, $stockQty),
            ':min_qty' => max(0, $minQty),
        ]);
    }

    public function updateProductStock(int $id, int $stockQty, int $minQty): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE purchase_products
             SET stock_qty = :stock_qty,
                 min_qty = :min_qty,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':stock_qty' => max(0, $stockQty),
            ':min_qty' => max(0, $minQty),
        ]);
    }

    public function createShortageAlert(
        string $productCode,
        string $details,
        string $priority,
        int $requestedByUserId,
        string $productName = ''
    ): string
    {
        $existing = $this->findOpenShortageByCode($productCode);
        if ($existing !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE purchase_shortage_alerts
                 SET updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([':id' => (int) $existing['id']]);
            return 'already_open';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO purchase_shortage_alerts (
                product_code, product_name, details, priority, status, requested_by_user_id, updated_at
             ) VALUES (
                :product_code, :product_name, :details, :priority, \'pending\', :requested_by_user_id, CURRENT_TIMESTAMP
             )'
        );

        $ok = $stmt->execute([
            ':product_code' => $productCode,
            ':product_name' => $productName !== '' ? $productName : $productCode,
            ':details' => $details !== '' ? $details : null,
            ':priority' => $priority,
            ':requested_by_user_id' => $requestedByUserId,
        ]);

        return $ok ? 'created' : 'error';
    }

    public function shortageAlerts(array $filters = [], ?int $requestedByUserId = null): array
    {
        $sql =
            'SELECT a.*,
                    requester.name AS requester_name,
                    accepter.name AS accepted_by_name,
                    resolver.name AS resolved_by_name,
                    closer.name AS closed_by_name
             FROM purchase_shortage_alerts a
             INNER JOIN users requester ON requester.id = a.requested_by_user_id
             LEFT JOIN users accepter ON accepter.id = a.accepted_by_user_id
             LEFT JOIN users resolver ON resolver.id = a.resolved_by_user_id
             LEFT JOIN users closer ON closer.id = a.closed_by_user_id';

        $params = [];
        $where = [];

        if ($requestedByUserId !== null && $requestedByUserId > 0) {
            $where[] = 'a.requested_by_user_id = :requested_by_user_id';
            $params[':requested_by_user_id'] = $requestedByUserId;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'a.status = :status';
            $params[':status'] = $status;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'date(a.created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $where[] = 'date(a.created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        $month = trim((string) ($filters['month'] ?? ''));
        if ($month !== '') {
            $where[] = 'strftime(\'%Y-%m\', a.created_at) = :month';
            $params[':month'] = $month;
        }

        $assignedToUserId = (int) ($filters['assigned_to_user_id'] ?? 0);
        if ($assignedToUserId > 0) {
            $where[] = 'a.accepted_by_user_id = :assigned_to_user_id';
            $params[':assigned_to_user_id'] = $assignedToUserId;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY CASE a.status
                    WHEN 'pending' THEN 0
                    WHEN 'accepted' THEN 1
                    WHEN 'resolved' THEN 2
                    WHEN 'closed' THEN 3
                    ELSE 4
                 END, a.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function acceptShortageAlert(int $id, int $acceptedByUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE purchase_shortage_alerts
             SET status = \'accepted\',
                 accepted_by_user_id = :accepted_by_user_id,
                 accepted_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND status = \'pending\''
        );
        return $stmt->execute([
            ':id' => $id,
            ':accepted_by_user_id' => $acceptedByUserId,
        ]);
    }

    public function resolveShortageAlert(int $id, int $resolvedByUserId, string $resolutionNote): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE purchase_shortage_alerts
             SET status = \'resolved\',
                 accepted_by_user_id = COALESCE(accepted_by_user_id, :resolved_by_user_id),
                 accepted_at = COALESCE(accepted_at, CURRENT_TIMESTAMP),
                 resolved_by_user_id = :resolved_by_user_id,
                 resolution_note = :resolution_note,
                 resolved_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND status IN (\'pending\', \'accepted\')'
        );

        return $stmt->execute([
            ':id' => $id,
            ':resolved_by_user_id' => $resolvedByUserId,
            ':resolution_note' => $resolutionNote !== '' ? $resolutionNote : null,
        ]);
    }

    public function closeShortageAlert(int $id, int $closedByUserId, string $closeNote): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE purchase_shortage_alerts
             SET status = \'closed\',
                 closed_by_user_id = :closed_by_user_id,
                 closed_at = CURRENT_TIMESTAMP,
                 resolution_note = CASE
                    WHEN :close_note IS NOT NULL AND :close_note <> \'\' THEN :close_note
                    ELSE resolution_note
                 END,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND status <> \'closed\''
        );

        return $stmt->execute([
            ':id' => $id,
            ':closed_by_user_id' => $closedByUserId,
            ':close_note' => $closeNote,
        ]);
    }

    public function resolveAllOpenShortages(int $resolvedByUserId, string $resolutionNote, array $filters = []): bool
    {
        return $this->bulkUpdateShortageStatus(
            "status = 'resolved',
             accepted_by_user_id = COALESCE(accepted_by_user_id, :user_id),
             accepted_at = COALESCE(accepted_at, CURRENT_TIMESTAMP),
             resolved_by_user_id = :user_id,
             resolved_at = CURRENT_TIMESTAMP,
             resolution_note = :note,
             updated_at = CURRENT_TIMESTAMP",
            ['pending', 'accepted'],
            $resolvedByUserId,
            $resolutionNote,
            $filters
        );
    }

    public function closeAllOpenShortages(int $closedByUserId, string $closeNote, array $filters = []): bool
    {
        return $this->bulkUpdateShortageStatus(
            "status = 'closed',
             closed_by_user_id = :user_id,
             closed_at = CURRENT_TIMESTAMP,
             resolution_note = CASE
                WHEN :note IS NOT NULL AND :note <> '' THEN :note
                ELSE resolution_note
             END,
             updated_at = CURRENT_TIMESTAMP",
            ['pending', 'accepted', 'resolved'],
            $closedByUserId,
            $closeNote,
            $filters
        );
    }

    public function createOperatorTiRequest(int $requestedByUserId, string $reason, string $details): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO operator_ti_requests (
                requested_by_user_id, reason, details, status, updated_at
             ) VALUES (
                :requested_by_user_id, :reason, :details, \'pending\', CURRENT_TIMESTAMP
             )'
        );
        return $stmt->execute([
            ':requested_by_user_id' => $requestedByUserId,
            ':reason' => $reason,
            ':details' => $details !== '' ? $details : null,
        ]);
    }

    public function operatorTiRequests(?int $requestedByUserId = null): array
    {
        $sql =
            'SELECT r.*,
                    requester.name AS requester_name,
                    reviewer.name AS reviewed_by_name
             FROM operator_ti_requests r
             INNER JOIN users requester ON requester.id = r.requested_by_user_id
             LEFT JOIN users reviewer ON reviewer.id = r.reviewed_by_user_id';
        $params = [];
        if ($requestedByUserId !== null && $requestedByUserId > 0) {
            $sql .= ' WHERE r.requested_by_user_id = :requested_by_user_id';
            $params[':requested_by_user_id'] = $requestedByUserId;
        }
        $sql .= ' ORDER BY CASE r.status WHEN \'pending\' THEN 0 ELSE 1 END, r.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reviewOperatorTiRequest(int $id, int $reviewedByUserId, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE operator_ti_requests
             SET status = :status,
                 reviewed_by_user_id = :reviewed_by_user_id,
                 reviewed_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND status = \'pending\''
        );
        return $stmt->execute([
            ':id' => $id,
            ':reviewed_by_user_id' => $reviewedByUserId,
            ':status' => $status,
        ]);
    }

    private function findOpenShortageByCode(string $productCode): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, status
             FROM purchase_shortage_alerts
             WHERE product_code = :product_code
               AND status IN (\'pending\', \'accepted\')
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([':product_code' => $productCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function bulkUpdateShortageStatus(
        string $setClause,
        array $allowedStatuses,
        int $userId,
        string $note,
        array $filters
    ): bool {
        $params = [
            ':user_id' => $userId,
            ':note' => $note !== '' ? $note : null,
        ];

        $where = [];
        $statusPlaceholders = [];
        foreach ($allowedStatuses as $index => $status) {
            $key = ':allowed_status_' . $index;
            $statusPlaceholders[] = $key;
            $params[$key] = $status;
        }
        $where[] = 'status IN (' . implode(', ', $statusPlaceholders) . ')';

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'date(created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $where[] = 'date(created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        $month = trim((string) ($filters['month'] ?? ''));
        if ($month !== '') {
            $where[] = 'strftime(\'%Y-%m\', created_at) = :month';
            $params[':month'] = $month;
        }

        $assignedToUserId = (int) ($filters['assigned_to_user_id'] ?? 0);
        if ($assignedToUserId > 0) {
            $where[] = 'accepted_by_user_id = :assigned_to_user_id';
            $params[':assigned_to_user_id'] = $assignedToUserId;
        }

        $sql = 'UPDATE purchase_shortage_alerts SET ' . $setClause . ' WHERE ' . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
