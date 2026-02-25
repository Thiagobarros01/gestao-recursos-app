<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StaffRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM staff ORDER BY name ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
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
