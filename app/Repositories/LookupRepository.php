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

    public function departments(): array
    {
        return $this->pdo->query('SELECT id, name FROM departments ORDER BY name ASC')->fetchAll();
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

    public function createDepartment(string $name): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO departments (name) VALUES (:name)');
        return $stmt->execute([':name' => $name]);
    }

    public function findCategoryById(int $id): ?array
    {
        return $this->findSimpleById('equipment_categories', $id);
    }

    public function findContractTypeById(int $id): ?array
    {
        return $this->findSimpleById('contract_types', $id);
    }

    public function findStatusById(int $id): ?array
    {
        return $this->findSimpleById('asset_statuses', $id);
    }

    public function findDepartmentById(int $id): ?array
    {
        return $this->findSimpleById('departments', $id);
    }

    public function updateCategory(int $id, string $name): bool
    {
        return $this->updateSimpleName('equipment_categories', $id, $name);
    }

    public function updateContractType(int $id, string $name): bool
    {
        return $this->updateSimpleName('contract_types', $id, $name);
    }

    public function updateStatus(int $id, string $name): bool
    {
        return $this->updateSimpleName('asset_statuses', $id, $name);
    }

    public function updateDepartment(int $id, string $name): bool
    {
        return $this->updateSimpleName('departments', $id, $name);
    }

    public function deleteCategory(int $id): bool
    {
        return $this->deleteSimpleById('equipment_categories', $id);
    }

    public function deleteContractType(int $id): bool
    {
        return $this->deleteSimpleById('contract_types', $id);
    }

    public function deleteStatus(int $id): bool
    {
        return $this->deleteSimpleById('asset_statuses', $id);
    }

    public function deleteDepartment(int $id): bool
    {
        return $this->deleteSimpleById('departments', $id);
    }

    public function categoryNameById(int $id): ?string
    {
        return $this->simpleNameById('equipment_categories', $id);
    }

    public function statusNameById(int $id): ?string
    {
        return $this->simpleNameById('asset_statuses', $id);
    }

    public function contractTypeNameById(int $id): ?string
    {
        return $this->simpleNameById('contract_types', $id);
    }

    public function departmentNameById(int $id): ?string
    {
        return $this->simpleNameById('departments', $id);
    }

    private function findSimpleById(string $table, int $id): ?array
    {
        $stmt = $this->pdo->prepare(sprintf('SELECT id, name FROM %s WHERE id = :id LIMIT 1', $table));
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function updateSimpleName(string $table, int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare(sprintf('UPDATE %s SET name = :name WHERE id = :id', $table));
        return $stmt->execute([':id' => $id, ':name' => $name]);
    }

    private function deleteSimpleById(string $table, int $id): bool
    {
        $stmt = $this->pdo->prepare(sprintf('DELETE FROM %s WHERE id = :id', $table));
        return $stmt->execute([':id' => $id]);
    }

    private function simpleNameById(string $table, int $id): ?string
    {
        $stmt = $this->pdo->prepare(sprintf('SELECT name FROM %s WHERE id = :id LIMIT 1', $table));
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : null;
    }
}
