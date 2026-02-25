<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AssetRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(string $search = '', ?string $department = null): array
    {
        $sql =
            'SELECT a.*, s.name AS staff_name,
                    c.name AS category_name,
                    ct.name AS contract_type_name,
                    st.name AS status_name
             FROM assets a
             LEFT JOIN staff s ON s.id = a.staff_id
             LEFT JOIN equipment_categories c ON c.id = a.category_id
             LEFT JOIN contract_types ct ON ct.id = a.contract_type_id
             LEFT JOIN asset_statuses st ON st.id = a.status_id';

        $params = [];
        $where = [];

        if ($department !== null) {
            $where[] = 's.department = :department';
            $params[':department'] = $department;
        }

        if ($search !== '') {
            $where[] = '(a.tag LIKE :q
                        OR a.serial_number LIKE :q
                        OR COALESCE(a.condition_state, \'\') LIKE :q
                        OR COALESCE(c.name, a.type) LIKE :q
                        OR COALESCE(st.name, a.status) LIKE :q
                        OR COALESCE(ct.name, \'\') LIKE :q
                        OR COALESCE(s.name, \'\') LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, ?string $department = null): ?array
    {
        $sql =
            'SELECT a.*, s.name AS staff_name,
                    c.name AS category_name,
                    ct.name AS contract_type_name,
                    st.name AS status_name
             FROM assets a
             LEFT JOIN staff s ON s.id = a.staff_id
             LEFT JOIN equipment_categories c ON c.id = a.category_id
             LEFT JOIN contract_types ct ON ct.id = a.contract_type_id
             LEFT JOIN asset_statuses st ON st.id = a.status_id
             WHERE a.id = :id';

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

    public function countAll(?string $department = null): int
    {
        if ($department === null) {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM assets a
             INNER JOIN staff s ON s.id = a.staff_id
             WHERE s.department = :department'
        );
        $stmt->execute([':department' => $department]);
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(string $status, ?string $department = null): int
    {
        $sql =
            'SELECT COUNT(*)
             FROM assets a
             LEFT JOIN asset_statuses st ON st.id = a.status_id
             LEFT JOIN staff s ON s.id = a.staff_id
             WHERE COALESCE(st.name, a.status) = :status';
        $params = [':status' => $status];

        if ($department !== null) {
            $sql .= ' AND s.department = :department';
            $params[':department'] = $department;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): bool
    {
        return $this->createAndGetId($data) > 0;
    }

    public function createAndGetId(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assets (
                type,
                tag,
                serial_number,
                status,
                condition_state,
                notes,
                document_path,
                staff_id,
                category_id,
                contract_type_id,
                status_id,
                observation,
                purchase_date,
                warranty_until,
                contract_until,
                returned_at,
                updated_at
            ) VALUES (
                :type,
                :tag,
                :serial_number,
                :status,
                :condition_state,
                :notes,
                :document_path,
                :staff_id,
                :category_id,
                :contract_type_id,
                :status_id,
                :observation,
                :purchase_date,
                :warranty_until,
                :contract_until,
                :returned_at,
                CURRENT_TIMESTAMP
            )'
        );

        $ok = $stmt->execute([
            ':type' => $data['category_name'],
            ':tag' => $data['tag'],
            ':serial_number' => $data['serial_number'] ?: null,
            ':status' => $data['status_name'],
            ':condition_state' => $data['condition_state'] ?: null,
            ':notes' => $data['observation'] ?: null,
            ':document_path' => $data['document_path'] ?: null,
            ':staff_id' => $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            ':category_id' => (int) $data['category_id'],
            ':contract_type_id' => (int) $data['contract_type_id'],
            ':status_id' => (int) $data['status_id'],
            ':observation' => $data['observation'] ?: null,
            ':purchase_date' => $data['purchase_date'] ?: null,
            ':warranty_until' => $data['warranty_until'] ?: null,
            ':contract_until' => $data['contract_until'] ?: null,
            ':returned_at' => $data['returned_at'] ?: null,
        ]);

        return $ok ? (int) $this->pdo->lastInsertId() : 0;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assets
             SET type = :type,
                 tag = :tag,
                 serial_number = :serial_number,
                 status = :status,
                 condition_state = :condition_state,
                 notes = :notes,
                 document_path = :document_path,
                 staff_id = :staff_id,
                 category_id = :category_id,
                 contract_type_id = :contract_type_id,
                 status_id = :status_id,
                 observation = :observation,
                 purchase_date = :purchase_date,
                 warranty_until = :warranty_until,
                 contract_until = :contract_until,
                 returned_at = :returned_at,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':type' => $data['category_name'],
            ':tag' => $data['tag'],
            ':serial_number' => $data['serial_number'] ?: null,
            ':status' => $data['status_name'],
            ':condition_state' => $data['condition_state'] ?: null,
            ':notes' => $data['observation'] ?: null,
            ':document_path' => $data['document_path'] ?: null,
            ':staff_id' => $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            ':category_id' => (int) $data['category_id'],
            ':contract_type_id' => (int) $data['contract_type_id'],
            ':status_id' => (int) $data['status_id'],
            ':observation' => $data['observation'] ?: null,
            ':purchase_date' => $data['purchase_date'] ?: null,
            ':warranty_until' => $data['warranty_until'] ?: null,
            ':contract_until' => $data['contract_until'] ?: null,
            ':returned_at' => $data['returned_at'] ?: null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM assets WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function transfer(int $assetId, int $staffId, ?int $statusId, ?string $statusName): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assets
             SET staff_id = :staff_id,
                 status_id = :status_id,
                 status = :status,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $assetId,
            ':staff_id' => $staffId,
            ':status_id' => $statusId,
            ':status' => $statusName,
        ]);
    }

    public function addMovement(array $movement): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO asset_movements (
                asset_id,
                movement_type,
                details,
                from_status,
                to_status,
                from_staff,
                to_staff,
                changed_by
            ) VALUES (
                :asset_id,
                :movement_type,
                :details,
                :from_status,
                :to_status,
                :from_staff,
                :to_staff,
                :changed_by
            )'
        );

        return $stmt->execute([
            ':asset_id' => (int) $movement['asset_id'],
            ':movement_type' => (string) $movement['movement_type'],
            ':details' => ($movement['details'] ?? '') !== '' ? (string) $movement['details'] : null,
            ':from_status' => ($movement['from_status'] ?? '') !== '' ? (string) $movement['from_status'] : null,
            ':to_status' => ($movement['to_status'] ?? '') !== '' ? (string) $movement['to_status'] : null,
            ':from_staff' => ($movement['from_staff'] ?? '') !== '' ? (string) $movement['from_staff'] : null,
            ':to_staff' => ($movement['to_staff'] ?? '') !== '' ? (string) $movement['to_staff'] : null,
            ':changed_by' => ($movement['changed_by'] ?? '') !== '' ? (string) $movement['changed_by'] : null,
        ]);
    }

    public function movementsByAssetId(int $assetId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM asset_movements
             WHERE asset_id = :asset_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
