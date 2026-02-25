<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HomeRequestRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(?string $department = null, ?int $requesterStaffId = null): array
    {
        $sql =
            'SELECT r.*,
                    a.tag AS asset_tag,
                    a.serial_number AS asset_serial,
                    a.condition_state AS asset_condition_state,
                    s.department AS requester_department
             FROM home_equipment_requests r
             INNER JOIN assets a ON a.id = r.asset_id
             INNER JOIN staff s ON s.id = r.requester_staff_id';

        $params = [];
        $where = [];
        if ($department !== null) {
            $where[] = 's.department = :department';
            $params[':department'] = $department;
        }
        if ($requesterStaffId !== null) {
            $where[] = 'r.requester_staff_id = :requester_staff_id';
            $params[':requester_staff_id'] = $requesterStaffId;
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.requested_at DESC, r.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, ?string $department = null, ?int $requesterStaffId = null): ?array
    {
        $sql =
            'SELECT r.*,
                    a.tag AS asset_tag,
                    a.serial_number AS asset_serial,
                    a.condition_state AS asset_condition_state,
                    s.department AS requester_department
             FROM home_equipment_requests r
             INNER JOIN assets a ON a.id = r.asset_id
             INNER JOIN staff s ON s.id = r.requester_staff_id
             WHERE r.id = :id';

        $params = [':id' => $id];
        if ($department !== null) {
            $sql .= ' AND s.department = :department';
            $params[':department'] = $department;
        }
        if ($requesterStaffId !== null) {
            $sql .= ' AND r.requester_staff_id = :requester_staff_id';
            $params[':requester_staff_id'] = $requesterStaffId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO home_equipment_requests (
                asset_id,
                requester_staff_id,
                requester_name,
                reason,
                condition_out,
                document_text
            ) VALUES (
                :asset_id,
                :requester_staff_id,
                :requester_name,
                :reason,
                :condition_out,
                :document_text
            )'
        );

        return $stmt->execute([
            ':asset_id' => (int) $data['asset_id'],
            ':requester_staff_id' => (int) $data['requester_staff_id'],
            ':requester_name' => (string) $data['requester_name'],
            ':reason' => (string) $data['reason'],
            ':condition_out' => (string) ($data['condition_out'] ?? ''),
            ':document_text' => (string) ($data['document_text'] ?? ''),
        ]);
    }

    public function approve(int $id, string $approvedBy, ?string $dueReturnDate): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE home_equipment_requests
             SET status = \'approved\',
                 approved_by = :approved_by,
                 approved_at = CURRENT_TIMESTAMP,
                 due_return_date = :due_return_date
             WHERE id = :id
               AND status = \'pending\''
        );

        return $stmt->execute([
            ':id' => $id,
            ':approved_by' => $approvedBy,
            ':due_return_date' => $dueReturnDate !== null && $dueReturnDate !== '' ? $dueReturnDate : null,
        ]);
    }

    public function reject(int $id, string $approvedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE home_equipment_requests
             SET status = \'rejected\',
                 approved_by = :approved_by,
                 approved_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND status = \'pending\''
        );

        return $stmt->execute([
            ':id' => $id,
            ':approved_by' => $approvedBy,
        ]);
    }

    public function markReturned(int $id, string $conditionIn): bool
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE home_equipment_requests
                 SET status = \'returned\',
                     returned_at = CURRENT_TIMESTAMP,
                     condition_in = :condition_in
                 WHERE id = :id
                   AND status = \'approved\''
            );

            $ok = $stmt->execute([
                ':id' => $id,
                ':condition_in' => $conditionIn,
            ]);

            if (!$ok || $stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }

            $assetId = $this->assetIdByRequestId($id);
            if ($assetId > 0) {
                $this->markAssetAsReturned($assetId, $conditionIn);
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function autoMarkOverdueAsReturned(): int
    {
        $this->pdo->beginTransaction();

        try {
            $dueRequestIds = $this->dueApprovedRequestIds();
            if (empty($dueRequestIds)) {
                $this->pdo->commit();
                return 0;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE home_equipment_requests
                 SET status = \'returned\',
                     returned_at = CURRENT_TIMESTAMP,
                     condition_in = COALESCE(NULLIF(condition_in, \'\'), \'Retorno automatico por prazo\')
                 WHERE id = :id
                   AND status = \'approved\''
            );

            $updated = 0;
            foreach ($dueRequestIds as $id) {
                $ok = $stmt->execute([':id' => $id]);
                if (!$ok || $stmt->rowCount() === 0) {
                    continue;
                }

                $updated++;
                $assetId = $this->assetIdByRequestId($id);
                if ($assetId > 0) {
                    $this->markAssetAsReturned($assetId, 'Retorno automatico por prazo');
                }
            }

            $this->pdo->commit();
            return $updated;
        } catch (\Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return 0;
        }
    }

    public function countByStatus(string $status, ?string $department = null, ?int $requesterStaffId = null): int
    {
        $sql =
            'SELECT COUNT(*)
             FROM home_equipment_requests r
             INNER JOIN staff s ON s.id = r.requester_staff_id
             WHERE r.status = :status';
        $params = [':status' => $status];

        if ($department !== null) {
            $sql .= ' AND s.department = :department';
            $params[':department'] = $department;
        }
        if ($requesterStaffId !== null) {
            $sql .= ' AND r.requester_staff_id = :requester_staff_id';
            $params[':requester_staff_id'] = $requesterStaffId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function dueApprovedRequestIds(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id
             FROM home_equipment_requests
             WHERE status = \'approved\'
               AND due_return_date IS NOT NULL
               AND due_return_date <> \'\'
               AND date(due_return_date) < date(\'now\', \'localtime\')'
        );
        $rows = $stmt->fetchAll();

        return array_map(static fn(array $row): int => (int) $row['id'], $rows);
    }

    private function assetIdByRequestId(int $requestId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT asset_id
             FROM home_equipment_requests
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $requestId]);
        return (int) $stmt->fetchColumn();
    }

    private function markAssetAsReturned(int $assetId, string $conditionIn): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assets
             SET status = \'Devolvido\',
                 status_id = (
                     SELECT id
                     FROM asset_statuses
                     WHERE name = \'Devolvido\'
                     LIMIT 1
                 ),
                 condition_state = :condition_state,
                 returned_at = date(\'now\', \'localtime\'),
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $assetId,
            ':condition_state' => $conditionIn !== '' ? $conditionIn : null,
        ]);
    }
}
