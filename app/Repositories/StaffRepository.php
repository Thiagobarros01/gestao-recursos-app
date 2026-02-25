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

    public function create(string $name, ?string $email, ?string $department): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO staff (name, email, department) VALUES (:name, :email, :department)');
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email ?: null,
            ':department' => $department ?: null,
        ]);
    }
}
