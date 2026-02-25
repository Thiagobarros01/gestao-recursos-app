<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\AccessControl;
use PDO;
use Throwable;

final class CommercialKanbanRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function boardsForUser(?array $user): array
    {
        if (!$user) {
            return [];
        }

        if (AccessControl::isFullAccess($user)) {
            return $this->pdo->query(
                'SELECT b.*,
                        owner.name AS owner_name,
                        (SELECT COUNT(*) FROM commercial_tasks t WHERE t.board_id = b.id) AS total_tasks
                 FROM commercial_boards b
                 INNER JOIN users owner ON owner.id = b.owner_user_id
                 ORDER BY b.updated_at DESC, b.id DESC'
            )->fetchAll();
        }

        $stmt = $this->pdo->prepare(
            'SELECT b.*,
                    owner.name AS owner_name,
                    (SELECT COUNT(*) FROM commercial_tasks t WHERE t.board_id = b.id) AS total_tasks
             FROM commercial_boards b
             INNER JOIN users owner ON owner.id = b.owner_user_id
             LEFT JOIN commercial_board_members m ON m.board_id = b.id AND m.user_id = :user_id
             WHERE b.owner_user_id = :user_id OR m.user_id IS NOT NULL
             ORDER BY b.updated_at DESC, b.id DESC'
        );
        $stmt->execute([':user_id' => (int) $user['id']]);
        return $stmt->fetchAll();
    }

    public function findBoardByIdForUser(int $boardId, ?array $user): ?array
    {
        if ($boardId <= 0 || !$user) {
            return null;
        }

        $params = [':id' => $boardId];
        $sql =
            'SELECT b.*, owner.name AS owner_name
             FROM commercial_boards b
             INNER JOIN users owner ON owner.id = b.owner_user_id
             WHERE b.id = :id';

        if (!AccessControl::isFullAccess($user)) {
            $sql .= ' AND (
                b.owner_user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM commercial_board_members m
                    WHERE m.board_id = b.id AND m.user_id = :user_id
                )
            )';
            $params[':user_id'] = (int) $user['id'];
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function canManageBoard(int $boardId, ?array $user): bool
    {
        if ($boardId <= 0 || !$user) {
            return false;
        }
        if (AccessControl::isFullAccess($user)) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM commercial_boards
             WHERE id = :id
               AND owner_user_id = :user_id'
        );
        $stmt->execute([
            ':id' => $boardId,
            ':user_id' => (int) $user['id'],
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createBoard(string $name, string $description, int $ownerUserId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO commercial_boards (name, description, owner_user_id, updated_at)
             VALUES (:name, :description, :owner_user_id, CURRENT_TIMESTAMP)'
        );
        $ok = $stmt->execute([
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':owner_user_id' => $ownerUserId,
        ]);

        if (!$ok) {
            return 0;
        }

        $boardId = (int) $this->pdo->lastInsertId();
        $this->addMember($boardId, $ownerUserId);
        return $boardId;
    }

    public function updateBoard(int $boardId, string $name, string $description): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE commercial_boards
             SET name = :name,
                 description = :description,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $boardId,
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
        ]);
    }

    public function membersByBoardId(int $boardId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.username, u.role, d.name AS department_name, s.name AS staff_name
             FROM commercial_board_members m
             INNER JOIN users u ON u.id = m.user_id
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN staff s ON s.id = u.staff_id
             WHERE m.board_id = :board_id
             ORDER BY u.name ASC'
        );
        $stmt->execute([':board_id' => $boardId]);
        return $stmt->fetchAll();
    }

    public function syncMembers(int $boardId, int $ownerUserId, array $userIds): bool
    {
        try {
            $this->pdo->beginTransaction();

            $delete = $this->pdo->prepare('DELETE FROM commercial_board_members WHERE board_id = :board_id');
            $delete->execute([':board_id' => $boardId]);

            $userIds[] = $ownerUserId;
            $unique = array_values(array_unique(array_map(static fn(mixed $v): int => (int) $v, $userIds)));
            foreach ($unique as $userId) {
                if ($userId <= 0) {
                    continue;
                }
                $this->addMember($boardId, $userId);
            }

            $this->touchBoard($boardId);
            $this->pdo->commit();
            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function tasksByBoardId(int $boardId, ?int $assigneeFilterUserId = null): array
    {
        $sql =
            'SELECT t.*,
                    assignee.name AS assignee_name,
                    assignee.username AS assignee_username
             FROM commercial_tasks t
             LEFT JOIN users assignee ON assignee.id = t.assignee_user_id
             WHERE t.board_id = :board_id';
        $params = [':board_id' => $boardId];

        if ($assigneeFilterUserId !== null) {
            $sql .= ' AND t.assignee_user_id = :assignee_user_id';
            $params[':assignee_user_id'] = $assigneeFilterUserId;
        }

        $sql .= ' ORDER BY t.status ASC, t.priority ASC, t.due_date ASC, t.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createTask(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO commercial_tasks (
                board_id, title, description, status, priority, assignee_user_id, due_date, created_by_user_id, updated_at
             ) VALUES (
                :board_id, :title, :description, :status, :priority, :assignee_user_id, :due_date, :created_by_user_id, CURRENT_TIMESTAMP
             )'
        );
        $ok = $stmt->execute([
            ':board_id' => (int) $data['board_id'],
            ':title' => (string) $data['title'],
            ':description' => ($data['description'] ?? '') !== '' ? (string) $data['description'] : null,
            ':status' => (string) $data['status'],
            ':priority' => (string) $data['priority'],
            ':assignee_user_id' => (int) ($data['assignee_user_id'] ?? 0) > 0 ? (int) $data['assignee_user_id'] : null,
            ':due_date' => ($data['due_date'] ?? '') !== '' ? (string) $data['due_date'] : null,
            ':created_by_user_id' => (int) $data['created_by_user_id'],
        ]);

        if (!$ok) {
            return 0;
        }

        $boardId = (int) $data['board_id'];
        $this->touchBoard($boardId);
        return (int) $this->pdo->lastInsertId();
    }

    public function findTaskById(int $taskId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM commercial_tasks
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $taskId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function moveTask(int $taskId, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE commercial_tasks
             SET status = :status,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $ok = $stmt->execute([
            ':id' => $taskId,
            ':status' => $status,
        ]);

        if ($ok) {
            $task = $this->findTaskById($taskId);
            if ($task) {
                $this->touchBoard((int) $task['board_id']);
            }
        }
        return $ok;
    }

    public function updateTask(int $taskId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE commercial_tasks
             SET title = :title,
                 description = :description,
                 priority = :priority,
                 assignee_user_id = :assignee_user_id,
                 due_date = :due_date,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $ok = $stmt->execute([
            ':id' => $taskId,
            ':title' => (string) $data['title'],
            ':description' => ($data['description'] ?? '') !== '' ? (string) $data['description'] : null,
            ':priority' => (string) $data['priority'],
            ':assignee_user_id' => (int) ($data['assignee_user_id'] ?? 0) > 0 ? (int) $data['assignee_user_id'] : null,
            ':due_date' => ($data['due_date'] ?? '') !== '' ? (string) $data['due_date'] : null,
        ]);

        if ($ok) {
            $task = $this->findTaskById($taskId);
            if ($task) {
                $this->touchBoard((int) $task['board_id']);
            }
        }

        return $ok;
    }

    public function deleteTask(int $taskId): bool
    {
        $task = $this->findTaskById($taskId);
        if ($task === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('DELETE FROM commercial_tasks WHERE id = :id');
        $ok = $stmt->execute([':id' => $taskId]);
        if ($ok) {
            $this->touchBoard((int) $task['board_id']);
        }
        return $ok;
    }

    private function addMember(int $boardId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT OR IGNORE INTO commercial_board_members (board_id, user_id)
             VALUES (:board_id, :user_id)'
        );
        $stmt->execute([
            ':board_id' => $boardId,
            ':user_id' => $userId,
        ]);
    }

    private function touchBoard(int $boardId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE commercial_boards
             SET updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([':id' => $boardId]);
    }
}
