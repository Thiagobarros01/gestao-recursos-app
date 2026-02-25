<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LookupRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function categories(): array
    {
        return $this->pdo->query('SELECT id, name FROM equipment_categories ORDER BY name ASC')->fetchAll();
    }

    public function contractTypes(): array
    {
        return $this->pdo->query('SELECT id, name FROM contract_types ORDER BY name ASC')->fetchAll();
    }

    public function statuses(): array
    {
        return $this->pdo->query('SELECT id, name FROM asset_statuses ORDER BY sort_order ASC, name ASC')->fetchAll();
    }

    public function createCategory(string $name): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO equipment_categories (name) VALUES (:name)');
        return $stmt->execute([':name' => $name]);
    }

    public function createContractType(string $name): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO contract_types (name) VALUES (:name)');
        return $stmt->execute([':name' => $name]);
    }

    public function createStatus(string $name): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO asset_statuses (name) VALUES (:name)');
        return $stmt->execute([':name' => $name]);
    }

    public function categoryNameById(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM equipment_categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : null;
    }

    public function statusNameById(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM asset_statuses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : null;
    }
}
