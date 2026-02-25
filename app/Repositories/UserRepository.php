<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, d.name AS department_name, s.name AS staff_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN staff s ON s.id = u.staff_id
             WHERE u.username = :username
             LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $user['allowed_routes'] = $this->allowedRoutesByUserId((int) $user['id']);
        return $user;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, d.name AS department_name, s.name AS staff_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN staff s ON s.id = u.staff_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $user['allowed_routes'] = $this->allowedRoutesByUserId((int) $user['id']);
        return $user;
    }

    public function all(): array
    {
        $rows = $this->pdo->query(
            'SELECT u.id, u.name, u.username, u.role, u.department_id, u.staff_id, d.name AS department_name, s.name AS staff_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN staff s ON s.id = u.staff_id
             ORDER BY u.name ASC'
        )->fetchAll();

        foreach ($rows as &$row) {
            $row['allowed_routes'] = $this->allowedRoutesByUserId((int) $row['id']);
        }

        return $rows;
    }

    public function create(
        string $name,
        string $username,
        string $passwordHash,
        string $role,
        ?int $departmentId,
        ?int $staffId,
        array $allowedRoutes
    ): bool {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, username, password_hash, role, department_id, staff_id)
                 VALUES (:name, :username, :password_hash, :role, :department_id, :staff_id)'
            );
            $ok = $stmt->execute([
                ':name' => $name,
                ':username' => $username,
                ':password_hash' => $passwordHash,
                ':role' => $role,
                ':department_id' => $departmentId,
                ':staff_id' => $staffId,
            ]);
            if (!$ok) {
                $this->pdo->rollBack();
                return false;
            }

            $userId = (int) $this->pdo->lastInsertId();
            $this->syncRoutes($userId, $allowedRoutes);
            $this->pdo->commit();
            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function update(
        int $id,
        string $name,
        string $username,
        ?string $passwordHash,
        string $role,
        ?int $departmentId,
        ?int $staffId,
        array $allowedRoutes
    ): bool {
        try {
            $this->pdo->beginTransaction();

            $sql = 'UPDATE users
                    SET name = :name,
                        username = :username,
                        role = :role,
                        department_id = :department_id,
                        staff_id = :staff_id';
            $params = [
                ':id' => $id,
                ':name' => $name,
                ':username' => $username,
                ':role' => $role,
                ':department_id' => $departmentId,
                ':staff_id' => $staffId,
            ];

            if ($passwordHash !== null) {
                $sql .= ', password_hash = :password_hash';
                $params[':password_hash'] = $passwordHash;
            }

            $sql .= ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($params);
            if (!$ok) {
                $this->pdo->rollBack();
                return false;
            }

            $this->syncRoutes($id, $allowedRoutes);
            $this->pdo->commit();
            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    private function allowedRoutesByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT route
             FROM user_route_permissions
             WHERE user_id = :user_id
             ORDER BY route ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(static fn(array $item): string => (string) $item['route'], $rows);
    }

    private function syncRoutes(int $userId, array $routes): void
    {
        $delete = $this->pdo->prepare('DELETE FROM user_route_permissions WHERE user_id = :user_id');
        $delete->execute([':user_id' => $userId]);

        if (empty($routes)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO user_route_permissions (user_id, route)
             VALUES (:user_id, :route)'
        );

        foreach (array_values(array_unique($routes)) as $route) {
            $route = trim((string) $route);
            if ($route === '') {
                continue;
            }

            $insert->execute([
                ':user_id' => $userId,
                ':route' => $route,
            ]);
        }
    }
}
