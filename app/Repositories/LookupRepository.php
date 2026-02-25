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

    public function findCategoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM equipment_categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findContractTypeById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM contract_types WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findStatusById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM asset_statuses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateCategory(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare('UPDATE equipment_categories SET name = :name WHERE id = :id');
        return $stmt->execute([':id' => $id, ':name' => $name]);
    }

    public function updateContractType(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare('UPDATE contract_types SET name = :name WHERE id = :id');
        return $stmt->execute([':id' => $id, ':name' => $name]);
    }

    public function updateStatus(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare('UPDATE asset_statuses SET name = :name WHERE id = :id');
        return $stmt->execute([':id' => $id, ':name' => $name]);
    }

    public function deleteCategory(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM equipment_categories WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function deleteContractType(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM contract_types WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function deleteStatus(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM asset_statuses WHERE id = :id');
        return $stmt->execute([':id' => $id]);
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

    public function contractTypeNameById(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM contract_types WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : null;
    }
}
