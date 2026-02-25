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
        $showArchived = (int) ($_GET['show_archived'] ?? 0) === 1;
        $boards = $this->kanban->boardsForUser($user, $showArchived);
        $filters = $this->readTaskFilters($_GET, $user);

        $selectedBoardId = (int) ($_GET['board'] ?? 0);
        if ($selectedBoardId <= 0 && !empty($boards)) {
            $selectedBoardId = (int) $boards[0]['id'];
        }

        $selectedBoard = $selectedBoardId > 0 ? $this->kanban->findBoardByIdForUser($selectedBoardId, $user, $showArchived) : null;
        $canManage = $selectedBoard ? $this->kanban->canManageBoard((int) $selectedBoard['id'], $user) : false;
        $canCreateBoard = $this->canCreateBoard();

        $members = [];
        $tasksByStatus = $this->emptyColumns();
        $managerSummary = null;
        $commentsByTaskId = [];
        $stageLabels = $this->stageLabelsFromBoard($selectedBoard);
        if ($selectedBoard) {
            $members = $this->kanban->membersByBoardId((int) $selectedBoard['id']);
            $assigneeFilter = $canManage ? null : (int) ($user['id'] ?? 0);
            $tasks = $this->kanban->tasksByBoardId(
                (int) $selectedBoard['id'],
                $assigneeFilter > 0 ? $assigneeFilter : null,
                $filters
            );
            $tasksByStatus = $this->groupTasks($tasks);
            $commentsByTaskId = $this->kanban->commentsByTaskIds(array_map(static fn(array $task): int => (int) $task['id'], $tasks));
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
            'commentsByTaskId' => $commentsByTaskId,
            'stageLabels' => $stageLabels,
            'managerSummary' => $managerSummary,
            'canManage' => $canManage,
            'canCreateBoard' => $canCreateBoard,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'filters' => $filters,
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
            'showArchived' => $showArchived,
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
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
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
        $stageTodo = trim((string) ($input['stage_name_todo'] ?? ''));
        $stageDoing = trim((string) ($input['stage_name_doing'] ?? ''));
        $stageReview = trim((string) ($input['stage_name_review'] ?? ''));
        $stageDone = trim((string) ($input['stage_name_done'] ?? ''));

        if ($boardId <= 0 || $name === '') {
            View::redirect('commercial.kanban&error=1');
        }

        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        try {
            $ok = $this->kanban->updateBoard($boardId, $name, $description, [
                'todo' => $stageTodo,
                'doing' => $stageDoing,
                'review' => $stageReview,
                'done' => $stageDone,
            ]);
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
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }
        if ((int) ($board['is_archived'] ?? 0) === 1) {
            View::redirect('commercial.kanban&error=4&board=' . $boardId . '&show_archived=1');
        }

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $customerName = trim((string) ($input['customer_name'] ?? ''));
        $companyName = trim((string) ($input['company_name'] ?? ''));
        $dealValue = trim((string) ($input['deal_value'] ?? ''));
        $tagName = trim((string) ($input['tag_name'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'todo'));
        $priority = trim((string) ($input['priority'] ?? 'media'));
        $assigneeUserId = (int) ($input['assignee_user_id'] ?? 0);
        $dueDate = trim((string) ($input['due_date'] ?? ''));

        if ($title === '' || !in_array($status, self::STATUSES, true) || !in_array($priority, self::PRIORITIES, true) || !$this->isValidDateOrEmpty($dueDate) || !$this->isValidMoneyOrEmpty($dealValue)) {
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
                'customer_name' => $customerName,
                'company_name' => $companyName,
                'deal_value' => $dealValue !== '' ? (float) str_replace(',', '.', $dealValue) : null,
                'tag_name' => $tagName,
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

        $board = $this->kanban->findBoardByIdForUser((int) $task['board_id'], Auth::user(), true);
        if ($board === null) {
            View::redirect('commercial.kanban&error=3');
        }
        if ((int) ($board['is_archived'] ?? 0) === 1) {
            View::redirect('commercial.kanban&error=4&board=' . (int) $task['board_id'] . '&show_archived=1');
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
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null) {
            View::redirect('commercial.kanban&error=3');
        }
        if ((int) ($board['is_archived'] ?? 0) === 1) {
            View::redirect('commercial.kanban&error=4&board=' . $boardId . '&show_archived=1');
        }
        if ((int) ($board['is_archived'] ?? 0) === 1) {
            View::redirect('commercial.kanban&error=4&board=' . $boardId . '&show_archived=1');
        }

        $canManage = $this->kanban->canManageBoard($boardId, Auth::user());
        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        $isAssignee = (int) ($task['assignee_user_id'] ?? 0) === $currentUserId;
        if (!$canManage && !$isAssignee) {
            View::redirect('commercial.kanban&error=3&board=' . $boardId);
        }

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $customerName = trim((string) ($input['customer_name'] ?? ''));
        $companyName = trim((string) ($input['company_name'] ?? ''));
        $dealValue = trim((string) ($input['deal_value'] ?? ''));
        $tagName = trim((string) ($input['tag_name'] ?? ''));
        $priority = trim((string) ($input['priority'] ?? 'media'));
        $dueDate = trim((string) ($input['due_date'] ?? ''));
        $assigneeUserId = (int) ($input['assignee_user_id'] ?? 0);

        if ($title === '' || !in_array($priority, self::PRIORITIES, true) || !$this->isValidDateOrEmpty($dueDate) || !$this->isValidMoneyOrEmpty($dealValue)) {
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
                'customer_name' => $customerName,
                'company_name' => $companyName,
                'deal_value' => $dealValue !== '' ? (float) str_replace(',', '.', $dealValue) : null,
                'tag_name' => $tagName,
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

    public function addComment(array $input): void
    {
        $taskId = (int) ($input['task_id'] ?? 0);
        $commentText = trim((string) ($input['comment_text'] ?? ''));
        if ($taskId <= 0 || $commentText === '') {
            View::redirect('commercial.kanban&error=1');
        }

        $task = $this->kanban->findTaskById($taskId);
        if ($task === null) {
            View::redirect('commercial.kanban&error=1');
        }

        $boardId = (int) $task['board_id'];
        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null) {
            View::redirect('commercial.kanban&error=3');
        }

        $canManage = $this->kanban->canManageBoard($boardId, Auth::user());
        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        $isAssignee = (int) ($task['assignee_user_id'] ?? 0) === $currentUserId;
        if (!$canManage && !$isAssignee) {
            View::redirect('commercial.kanban&error=3&board=' . $boardId);
        }

        try {
            $ok = $this->kanban->createTaskComment($taskId, $currentUserId, $commentText);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=11&board=' . $boardId);
    }

    public function deleteComment(array $input): void
    {
        $commentId = (int) ($input['comment_id'] ?? 0);
        if ($commentId <= 0) {
            View::redirect('commercial.kanban&error=1');
        }

        $comment = $this->kanban->findCommentById($commentId);
        if ($comment === null) {
            View::redirect('commercial.kanban&error=1');
        }

        $task = $this->kanban->findTaskById((int) ($comment['task_id'] ?? 0));
        if ($task === null) {
            View::redirect('commercial.kanban&error=1');
        }

        $boardId = (int) $task['board_id'];
        $canManage = $this->kanban->canManageBoard($boardId, Auth::user());
        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        if (!$canManage && (int) ($comment['user_id'] ?? 0) !== $currentUserId) {
            View::redirect('commercial.kanban&error=3&board=' . $boardId);
        }

        try {
            $ok = $this->kanban->deleteTaskComment($commentId);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=12&board=' . $boardId);
    }

    public function archiveBoard(array $input): void
    {
        $boardId = (int) ($input['board_id'] ?? 0);
        if ($boardId <= 0) {
            View::redirect('commercial.kanban&error=1');
        }

        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        try {
            $ok = $this->kanban->archiveBoard($boardId);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId);
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId);
        }

        View::redirect('commercial.kanban&ok=8&show_archived=1');
    }

    public function unarchiveBoard(array $input): void
    {
        $boardId = (int) ($input['board_id'] ?? 0);
        if ($boardId <= 0) {
            View::redirect('commercial.kanban&error=1');
        }

        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        try {
            $ok = $this->kanban->unarchiveBoard($boardId);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId . '&show_archived=1');
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId . '&show_archived=1');
        }

        View::redirect('commercial.kanban&ok=9&board=' . $boardId);
    }

    public function deleteBoard(array $input): void
    {
        $boardId = (int) ($input['board_id'] ?? 0);
        if ($boardId <= 0) {
            View::redirect('commercial.kanban&error=1');
        }

        $board = $this->kanban->findBoardByIdForUser($boardId, Auth::user(), true);
        if ($board === null || !$this->kanban->canManageBoard($boardId, Auth::user())) {
            View::redirect('commercial.kanban&error=3');
        }

        try {
            $ok = $this->kanban->deleteBoard($boardId);
            if (!$ok) {
                View::redirect('commercial.kanban&error=2&board=' . $boardId . '&show_archived=1');
            }
        } catch (Throwable) {
            View::redirect('commercial.kanban&error=2&board=' . $boardId . '&show_archived=1');
        }

        View::redirect('commercial.kanban&ok=10&show_archived=1');
    }

    private function canCreateBoard(): bool
    {
        $user = Auth::user();
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

    private function isValidMoneyOrEmpty(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        $normalized = str_replace(',', '.', $value);
        return is_numeric($normalized);
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

    private function readTaskFilters(array $source, ?array $user): array
    {
        $priority = trim((string) ($source['priority_filter'] ?? ''));
        if (!in_array($priority, self::PRIORITIES, true)) {
            $priority = '';
        }

        $assigneeUserId = (int) ($source['assignee_user_id'] ?? 0);
        $onlyMine = (int) ($source['only_mine'] ?? 0) === 1;
        if ($onlyMine) {
            $assigneeUserId = (int) ($user['id'] ?? 0);
        }

        return [
            'query' => trim((string) ($source['q'] ?? '')),
            'priority' => $priority,
            'assignee_user_id' => $assigneeUserId > 0 ? $assigneeUserId : 0,
            'only_overdue' => (int) ((string) ($source['only_overdue'] ?? '0') === '1'),
            'due_today' => (int) ((string) ($source['due_today'] ?? '0') === '1'),
            'only_mine' => $onlyMine ? 1 : 0,
        ];
    }

    private function stageLabelsFromBoard(?array $board): array
    {
        return [
            'todo' => trim((string) ($board['stage_name_todo'] ?? '')) ?: 'Prospeccao',
            'doing' => trim((string) ($board['stage_name_doing'] ?? '')) ?: 'Contato',
            'review' => trim((string) ($board['stage_name_review'] ?? '')) ?: 'Proposta',
            'done' => trim((string) ($board['stage_name_done'] ?? '')) ?: 'Fechado',
        ];
    }

    private function buildManagerSummary(array $tasks): array
    {
        $byStatus = array_fill_keys(self::STATUSES, 0);
        $byAssignee = [];
        $totalValue = 0.0;

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

            $dealValue = (float) ($task['deal_value'] ?? 0);
            if ($dealValue > 0) {
                $totalValue += $dealValue;
            }
        }

        arsort($byAssignee);

        return [
            'total' => count($tasks),
            'total_value' => $totalValue,
            'by_status' => $byStatus,
            'by_assignee' => $byAssignee,
        ];
    }
}
