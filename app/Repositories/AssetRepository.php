<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AssetRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(string $search = ''): array
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

        if ($search === '') {
            $stmt = $this->pdo->query($sql . ' ORDER BY a.created_at DESC');
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare(
            $sql . ' WHERE a.tag LIKE :q
                      OR a.serial_number LIKE :q
                      OR COALESCE(c.name, a.type) LIKE :q
                      OR COALESCE(st.name, a.status) LIKE :q
                      OR COALESCE(ct.name, \'\') LIKE :q
                      OR COALESCE(s.name, \'\') LIKE :q
                      ORDER BY a.created_at DESC'
        );
        $stmt->execute([':q' => '%' . $search . '%']);
        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM assets a
             LEFT JOIN asset_statuses st ON st.id = a.status_id
             WHERE COALESCE(st.name, a.status) = :status'
        );
        $stmt->execute([':status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assets (
                type,
                tag,
                serial_number,
                status,
                notes,
                document_path,
                staff_id,
                category_id,
                contract_type_id,
                status_id,
                observation,
                updated_at
            ) VALUES (
                :type,
                :tag,
                :serial_number,
                :status,
                :notes,
                :document_path,
                :staff_id,
                :category_id,
                :contract_type_id,
                :status_id,
                :observation,
                CURRENT_TIMESTAMP
            )'
        );

        return $stmt->execute([
            ':type' => $data['category_name'],
            ':tag' => $data['tag'],
            ':serial_number' => $data['serial_number'] ?: null,
            ':status' => $data['status_name'],
            ':notes' => $data['observation'] ?: null,
            ':document_path' => $data['document_path'] ?: null,
            ':staff_id' => $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            ':category_id' => (int) $data['category_id'],
            ':contract_type_id' => (int) $data['contract_type_id'],
            ':status_id' => (int) $data['status_id'],
            ':observation' => $data['observation'] ?: null,
        ]);
    }
}
