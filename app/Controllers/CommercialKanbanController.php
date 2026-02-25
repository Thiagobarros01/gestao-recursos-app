<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\CommercialKanbanRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use Throwable;

final class CommercialKanbanController
{
    private const STATUSES = ['todo', 'doing', 'review', 'done'];
    private const PRIORITIES = ['alta', 'media', 'baixa'];

    public function __construct(
        private CommercialKanbanRepository $kanban,
        private UserRepository $users
    ) {
    }

    public function index(): void
    {
        $user = Auth::user();
        $boards = $this->kanban->boardsForUser($user);

        $selectedBoardId = (int) ($_GET['board'] ?? 0);
        if ($selectedBoardId <= 0 && !empty($boards)) {
            $selectedBoardId = (int) $boards[0]['id'];
        }

        $selectedBoard = $selectedBoardId > 0 ? $this->kanban->findBoardByIdForUser($selectedBoardId, $user) : null;
        $canManage = $selectedBoard ? $this->kanban->canManageBoard((int) $selectedBoard['id'], $user) : false;
        $canCreateBoard = $this->canCreateBoard();

        $members = [];
        $tasksByStatus = $this->emptyColumns();
        $managerSummary = null;
        if ($selectedBoard) {
            $members = $this->kanban->membersByBoardId((int) $selectedBoard['id']);
            $assigneeFilter = $canManage ? null : (int) ($user['id'] ?? 0);
            $tasks = $this->kanban->tasksByBoardId((int) $selectedBoard['id'], $assigneeFilter > 0 ? $assigneeFilter : null);
            $tasksByStatus = $this->groupTasks($tasks);
            if ($canManage) {
                $managerSummary = $this->buildManagerSummary($tasks);
            }
        }

        View::render('commercial/kanban', [
            'title' => 'Kanban Comercial',
            'currentRoute' => 'commercial.kanban',
            'boards' => $boards,
            'selectedBoard' => $selectedBoard,
            'members' => $members,
            'users' => $this->users->all(),
            'tasksByStatus' => $tasksByStatus,
            'managerSummary' => $managerSummary,
            'canManage' => $canManage,
            'canCreateBoard' => $canCreateBoard,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function createBoard(array $input): void
    {
        if (!$this->canCreateBoard()) {
            View::redirect('commercial.kanban&error=3');
        }

        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        if ($name === '') {
            View::redirect('commercial.kanban&error=1');
        }

        try {
            $boardId = $this->kanban->createBoard($name, $description, (int) (Auth::user()['id'] ?? 0));
            if ($boardId <= 0) {
                View::redirect('commercial.kanban&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2');
        }

        View::redirect('commercial.kanban&ok=1&board=' . $boardId);
    }

    public function updateMembers(array $input): void
    {
        $boardId = (int) ($input['board_id'] ?? 0);
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user());
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        $members = $input['member_user_ids'] ?? [];
        if (!is_array($members)) {
            $members = [];
        }

        try {
            $ok = $this->kanban->syncMembers($boardId, (int) $board['owner_user_id'], $members);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=2&board=' . $boardId);
    }

    public function updateBoard(array $input): void
    {
        $boardId = (int) ($input['board_id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        if ($boardId <= 0 || $name === '') {
            View::redirect('commercial.kanban&error=1');
        }

        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user());
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        try {
            $ok = $this->kanban->updateBoard($boardId, $name, $description);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=6&board=' . $boardId);
    }

    public function createTask(array $input): void
    {
        $boardId = (int) ($input['board_id'] ?? 0);
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user());
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'todo'));
        $priority = trim((string) ($input['priority'] ?? 'media'));
        $assigneeUserId = (int) ($input['assignee_user_id'] ?? 0);
        $dueDate = trim((string) ($input['due_date'] ?? ''));

        if ($title === '' || !in_array($status, self::STATUSES, true) || !in_array($priority, self::PRIORITIES, true) || !$this->isValidDateOrEmpty($dueDate)) {
            View::redirect('commercial.kanban&error=1&board=' . $boardId);
        }

        if ($assigneeUserId > 0 && !$this->assigneeBelongsToBoard($boardId, $assigneeUserId)) {
            View::redirect('commercial.kanban&error=1&board=' . $boardId);
        }

        try {
            $taskId = $this->kanban->createTask([
                'board_id' => $boardId,
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'priority' => $priority,
                'assignee_user_id' => $assigneeUserId > 0 ? $assigneeUserId : null,
                'due_date' => $dueDate,
                'created_by_user_id' => (int) (Auth::user()['id'] ?? 0),
            ]);
            if ($taskId <= 0) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=3&board=' . $boardId);
    }

    public function moveTask(array $input): void
    {
        $taskId = (int) ($input['task_id'] ?? 0);
        $toStatus = trim((string) ($input['to_status'] ?? ''));
        if ($taskId <= 0 || !in_array($toStatus, self::STATUSES, true)) {
            View::redirect('commercial.kanban&error=1');
        }

        $task = $this->kanban->findTaskById($taskId);
        if ($task === null) {
            View::redirect('commercial.kanban&error=1');
        }

        $board = $this->kanban->findBoardByIdForUser((int) $task['board_id'], Auth::user());
        if ($board === null) {
            View::redirect('commercial.kanban&error=3');
        }

        $canManage = $this->kanban->canManageBoard((int) $task['board_id'], Auth::user());
        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        $isAssignee = (int) ($task['assignee_user_id'] ?? 0) === $currentUserId;
        if (!$canManage && !$isAssignee) {
            View::redirect('commercial.kanban&error=3&board=' . (int) $task['board_id']);
        }

        try {
            $ok = $this->kanban->moveTask($taskId, $toStatus);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . (int) $task['board_id']);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . (int) $task['board_id']);
        }

        View::redirect('commercial.kanban&ok=4&board=' . (int) $task['board_id']);
    }

    public function updateTask(array $input): void
    {
        $taskId = (int) ($input['task_id'] ?? 0);
        if ($taskId <= 0) {
            View::redirect('commercial.kanban&error=1');
        }

        $task = $this->kanban->findTaskById($taskId);
        if ($task === null) {
            View::redirect('commercial.kanban&error=1');
        }

        $boardId = (int) $task['board_id'];
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user());
        if ($board === null) {
            View::redirect('commercial.kanban&error=3');
        }

        $canManage = $this->kanban->canManageBoard($boardId, Auth::user());
        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        $isAssignee = (int) ($task['assignee_user_id'] ?? 0) === $currentUserId;
        if (!$canManage && !$isAssignee) {
            View::redirect('commercial.kanban&error=3&board=' . $boardId);
        }

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $priority = trim((string) ($input['priority'] ?? 'media'));
        $dueDate = trim((string) ($input['due_date'] ?? ''));
        $assigneeUserId = (int) ($input['assignee_user_id'] ?? 0);

        if ($title === '' || !in_array($priority, self::PRIORITIES, true) || !$this->isValidDateOrEmpty($dueDate)) {
            View::redirect('commercial.kanban&error=1&board=' . $boardId);
        }
        if ($assigneeUserId > 0 && !$this->assigneeBelongsToBoard($boardId, $assigneeUserId)) {
            View::redirect('commercial.kanban&error=1&board=' . $boardId);
        }

        if (!$canManage) {
            $assigneeUserId = $currentUserId;
        }

        try {
            $ok = $this->kanban->updateTask($taskId, [
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'assignee_user_id' => $assigneeUserId > 0 ? $assigneeUserId : null,
                'due_date' => $dueDate,
            ]);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=7&board=' . $boardId);
    }

    public function deleteTask(array $input): void
    {
        $taskId = (int) ($input['task_id'] ?? 0);
        if ($taskId <= 0) {
            View::redirect('commercial.kanban&error=1');
        }

        $task = $this->kanban->findTaskById($taskId);
        if ($task === null || !$this->kanban->canManageBoard((int) $task['board_id'], Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        try {
            $ok = $this->kanban->deleteTask($taskId);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . (int) $task['board_id']);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . (int) $task['board_id']);
        }

        View::redirect('commercial.kanban&ok=5&board=' . (int) $task['board_id']);
    }

    private function canCreateBoard(): bool
    {
        $user = Auth::user();
        if (AccessControl::isFullAccess($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $role = AccessControl::normalizeRole($user['role'] ?? null);
        if ($role === 'gestor') {
            return true;
        }

        return AccessControl::canAccessRoute('commercial.kanban.board.store', $user);
    }

    private function assigneeBelongsToBoard(int $boardId, int $assigneeUserId): bool
    {
        $members = $this->kanban->membersByBoardId($boardId);
        foreach ($members as $member) {
            if ((int) $member['id'] === $assigneeUserId) {
                return true;
            }
        }
        return false;
    }

    private function isValidDateOrEmpty(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function emptyColumns(): array
    {
        $columns = [];
        foreach (self::STATUSES as $status) {
            $columns[$status] = [];
        }
        return $columns;
    }

    private function groupTasks(array $tasks): array
    {
        $columns = $this->emptyColumns();
        foreach ($tasks as $task) {
            $status = (string) ($task['status'] ?? 'todo');
            if (!isset($columns[$status])) {
                $status = 'todo';
            }
            $columns[$status][] = $task;
        }
        return $columns;
    }

    private function buildManagerSummary(array $tasks): array
    {
        $byStatus = array_fill_keys(self::STATUSES, 0);
        $byAssignee = [];

        foreach ($tasks as $task) {
            $status = (string) ($task['status'] ?? 'todo');
            if (!isset($byStatus[$status])) {
                $status = 'todo';
            }
            $byStatus[$status]++;

            $assigneeName = trim((string) ($task['assignee_name'] ?? ''));
            if ($assigneeName === '') {
                $assigneeName = 'Sem responsavel';
            }
            if (!isset($byAssignee[$assigneeName])) {
                $byAssignee[$assigneeName] = 0;
            }
            $byAssignee[$assigneeName]++;
        }

        arsort($byAssignee);

        return [
            'total' => count($tasks),
            'by_status' => $byStatus,
            'by_assignee' => $byAssignee,
        ];
    }
}
