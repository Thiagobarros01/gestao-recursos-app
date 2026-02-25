<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HomeRequestRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(?string $department = null): array
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
        if ($department !== null) {
            $sql .= ' WHERE s.department = :department';
            $params[':department'] = $department;
        }

        $sql .= ' ORDER BY r.requested_at DESC, r.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, ?string $department = null): ?array
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
        $stmt = $this->pdo->prepare(
            'UPDATE home_equipment_requests
             SET status = \'returned\',
                 returned_at = CURRENT_TIMESTAMP,
                 condition_in = :condition_in
             WHERE id = :id
               AND status = \'approved\''
        );

        return $stmt->execute([
            ':id' => $id,
            ':condition_in' => $conditionIn,
        ]);
    }

    public function autoMarkOverdueAsReturned(): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE home_equipment_requests
             SET status = \'returned\',
                 returned_at = CURRENT_TIMESTAMP,
                 condition_in = COALESCE(NULLIF(condition_in, \'\'), \'Retorno automatico por prazo\')
             WHERE status = \'approved\'
               AND due_return_date IS NOT NULL
               AND due_return_date <> \'\'
               AND date(due_return_date) < date(\'now\', \'localtime\')'
        );
        $stmt->execute();

        return $stmt->rowCount();
    }
}
