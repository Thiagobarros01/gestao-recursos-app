<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StaffRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $department = null, ?int $staffId = null): array
    {
        if ($staffId !== null) {
            $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE id = :id ORDER BY name ASC');
            $stmt->execute([':id' => $staffId]);
            return $stmt->fetchAll();
        }

        if ($department === null) {
            return $this->pdo->query('SELECT * FROM staff ORDER BY name ASC')->fetchAll();
        }

        $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE department = :department ORDER BY name ASC');
        $stmt->execute([':department' => $department]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, ?string $department = null, ?int $staffId = null): ?array
    {
        if ($staffId !== null && $staffId > 0 && $id !== $staffId) {
            return null;
        }

        if ($department === null) {
            $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE id = :id AND department = :department LIMIT 1');
            $stmt->execute([':id' => $id, ':department' => $department]);
        }

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, ?string $email, ?string $department): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO staff (name, email, department) VALUES (:name, :email, :department)');
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email ?: null,
            ':department' => $department ?: null,
        ]);
    }

    public function update(int $id, string $name, ?string $email, ?string $department): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE staff
             SET name = :name, email = :email, department = :department
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email ?: null,
            ':department' => $department ?: null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM staff WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
