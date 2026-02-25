<?php
$statusLabels = [
    'todo' => 'A Fazer',
    'doing' => 'Em Progresso',
    'review' => 'Revisao',
    'done' => 'Concluido',
];
?>
<section class="page-head">
    <h1>Kanban Comercial</h1>
    <p>Quadros por equipe com tarefas e atribuicao direta para os colaboradores.</p>
</section>

<?php if ((string) ($success ?? '') === '1'): ?>
    <div class="alert success">Quadro criado com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '2'): ?>
    <div class="alert success">Membros do quadro atualizados.</div>
<?php elseif ((string) ($success ?? '') === '3'): ?>
    <div class="alert success">Tarefa criada com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '4'): ?>
    <div class="alert success">Tarefa movida com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '5'): ?>
    <div class="alert success">Tarefa removida.</div>
<?php elseif ((string) ($success ?? '') === '6'): ?>
    <div class="alert success">Quadro atualizado.</div>
<?php elseif ((string) ($success ?? '') === '7'): ?>
    <div class="alert success">Tarefa atualizada.</div>
<?php endif; ?>

<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Dados invalidos para operacao.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel concluir a operacao.</div>
<?php elseif ((string) ($error ?? '') === '3'): ?>
    <div class="alert error">Sem permissao para esta acao.</div>
<?php endif; ?>

<section class="card-grid">
    <article class="card">
        <h2>Quadros</h2>
        <?php if ($canCreateBoard): ?>
            <form method="post" action="index.php?r=commercial.kanban.board.store" class="form-grid two">
                <input type="text" name="name" placeholder="Nome do quadro" required>
                <input type="text" name="description" placeholder="Descricao (opcional)">
                <div class="actions-inline full">
                    <button type="submit">Criar quadro</button>
                </div>
            </form>
        <?php endif; ?>

        <ul class="simple-list stack-list">
            <?php foreach ($boards as $board): ?>
                <li class="stack-item">
                    <div>
                        <strong><?= htmlspecialchars((string) $board['name']) ?></strong>
                        <p class="muted small">Tarefas: <?= (int) ($board['total_tasks'] ?? 0) ?> | Dono: <?= htmlspecialchars((string) ($board['owner_name'] ?? '')) ?></p>
                    </div>
                    <a class="btn btn-muted" href="index.php?r=commercial.kanban&board=<?= (int) $board['id'] ?>">Abrir</a>
                </li>
            <?php endforeach; ?>
            <?php if (empty($boards)): ?>
                <li>Nenhum quadro disponivel.</li>
            <?php endif; ?>
        </ul>
    </article>

    <?php if ($selectedBoard): ?>
        <article class="card">
            <div class="kanban-head">
                <h2>Quadro: <?= htmlspecialchars((string) $selectedBoard['name']) ?></h2>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-muted" data-toggle-target="editBoardBox">Renomear quadro</button>
                <?php endif; ?>
            </div>
            <?php if (!empty($selectedBoard['description'])): ?>
                <p class="muted"><?= htmlspecialchars((string) $selectedBoard['description']) ?></p>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <div id="editBoardBox" class="quick-box hidden">
                    <form method="post" action="index.php?r=commercial.kanban.board.update" class="form-grid two">
                        <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars((string) $selectedBoard['name']) ?>" required>
                        <input type="text" name="description" value="<?= htmlspecialchars((string) ($selectedBoard['description'] ?? '')) ?>" placeholder="Descricao">
                        <div class="actions-inline full">
                            <button type="submit">Salvar nome</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <form method="post" action="index.php?r=commercial.kanban.members.update" class="form-grid two">
                    <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                    <div class="full">
                        <label>Colaboradores com acesso ao quadro</label>
                        <div class="permission-grid">
                            <?php
                                $memberIds = array_map(static fn(array $m): int => (int) $m['id'], $members);
                            ?>
                            <?php foreach ($users as $userRow): ?>
                                <label class="permission-item">
                                    <input
                                        type="checkbox"
                                        name="member_user_ids[]"
                                        value="<?= (int) $userRow['id'] ?>"
                                        <?= in_array((int) $userRow['id'], $memberIds, true) ? 'checked' : '' ?>
                                    >
                                    <span>
                                        <?= htmlspecialchars((string) $userRow['name']) ?>
                                        <small class="muted">(<?= htmlspecialchars((string) $userRow['username']) ?><?= !empty($userRow['department_name']) ? ' - ' . htmlspecialchars((string) $userRow['department_name']) : '' ?>)</small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="actions-inline full">
                        <button type="submit">Salvar acessos</button>
                    </div>
                </form>

                <hr>

                <form method="post" action="index.php?r=commercial.kanban.task.store" class="form-grid three">
                    <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                    <div>
                        <label>Titulo</label>
                        <input type="text" name="title" required>
                    </div>
                    <div>
                        <label>Responsavel</label>
                        <select name="assignee_user_id">
                            <option value="">Sem responsavel</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= (int) $member['id'] ?>">
                                    <?= htmlspecialchars((string) $member['name']) ?> (@<?= htmlspecialchars((string) $member['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Prioridade</label>
                        <select name="priority">
                            <option value="alta">Alta</option>
                            <option value="media" selected>Media</option>
                            <option value="baixa">Baixa</option>
                        </select>
                    </div>
                    <div>
                        <label>Coluna inicial</label>
                        <select name="status">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars((string) $status) ?>">
                                    <?= htmlspecialchars((string) ($statusLabels[$status] ?? strtoupper((string) $status))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Prazo</label>
                        <input type="date" name="due_date">
                    </div>
                    <div class="full">
                        <label>Descricao</label>
                        <textarea name="description" rows="3" placeholder="Detalhes da tarefa"></textarea>
                    </div>
                    <div class="actions-inline full">
                        <button type="submit">Criar tarefa</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="muted">Voce visualiza apenas tarefas atribuidas para seu usuario.</p>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</section>

<?php if ($selectedBoard): ?>
    <?php if ($canManage && $managerSummary): ?>
        <section class="card">
            <h2>Visao da gestora</h2>
            <div class="stats-grid">
                <article class="stat-card"><p>Total</p><h3><?= (int) $managerSummary['total'] ?></h3></article>
                <article class="stat-card"><p>A Fazer</p><h3><?= (int) $managerSummary['by_status']['todo'] ?></h3></article>
                <article class="stat-card"><p>Em Progresso</p><h3><?= (int) $managerSummary['by_status']['doing'] ?></h3></article>
                <article class="stat-card"><p>Revisao</p><h3><?= (int) $managerSummary['by_status']['review'] ?></h3></article>
                <article class="stat-card ok"><p>Concluido</p><h3><?= (int) $managerSummary['by_status']['done'] ?></h3></article>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Responsavel</th>
                            <th>Tarefas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managerSummary['by_assignee'] as $assigneeName => $count): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $assigneeName) ?></td>
                                <td><?= (int) $count ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($managerSummary['by_assignee'])): ?>
                            <tr><td colspan="2">Sem tarefas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="kanban-board" data-board-id="<?= (int) $selectedBoard['id'] ?>">
        <?php foreach ($statuses as $status): ?>
            <article class="kanban-column <?= $status === 'done' ? 'done-col' : '' ?>" data-status="<?= htmlspecialchars((string) $status) ?>">
                <h3>
                    <?= htmlspecialchars((string) ($statusLabels[$status] ?? strtoupper((string) $status))) ?>
                    <span class="muted small">(<?= count($tasksByStatus[$status]) ?>)</span>
                </h3>
                <?php foreach ($tasksByStatus[$status] as $task): ?>
                    <div class="kanban-card <?= (string) $task['status'] === 'done' ? 'done-card' : '' ?>" draggable="true" data-task-id="<?= (int) $task['id'] ?>">
                        <p class="kanban-title"><?= htmlspecialchars((string) $task['title']) ?></p>
                        <?php if (!empty($task['description'])): ?>
                            <p class="muted small"><?= nl2br(htmlspecialchars((string) $task['description'])) ?></p>
                        <?php endif; ?>
                        <p class="muted small">
                            Prioridade: <?= htmlspecialchars((string) $task['priority']) ?><br>
                            Responsavel: <?= htmlspecialchars((string) ($task['assignee_name'] ?? 'Nao definido')) ?><br>
                            <?php if (!empty($task['due_date'])): ?>
                                Prazo: <?= htmlspecialchars((string) $task['due_date']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="actions-inline">
                            <button type="button" class="btn btn-muted" data-toggle-target="editTask<?= (int) $task['id'] ?>">Editar</button>
                            <?php foreach ($statuses as $moveStatus): ?>
                                <?php if ($moveStatus === (string) $task['status']): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <form method="post" action="index.php?r=commercial.kanban.task.move">
                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <input type="hidden" name="to_status" value="<?= htmlspecialchars((string) $moveStatus) ?>">
                                    <button type="submit" class="btn btn-muted"><?= htmlspecialchars((string) ($statusLabels[$moveStatus] ?? strtoupper((string) $moveStatus))) ?></button>
                                </form>
                            <?php endforeach; ?>
                            <?php if ($canManage): ?>
                                <form method="post" action="index.php?r=commercial.kanban.task.delete" onsubmit="return confirm('Excluir tarefa?');">
                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <button type="submit" class="btn-danger">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div id="editTask<?= (int) $task['id'] ?>" class="quick-box hidden">
                            <form method="post" action="index.php?r=commercial.kanban.task.update" class="form-grid two">
                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                <input type="text" name="title" value="<?= htmlspecialchars((string) $task['title']) ?>" required>
                                <select name="priority">
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?= htmlspecialchars((string) $priority) ?>" <?= (string) $task['priority'] === (string) $priority ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) ucfirst((string) $priority)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="assignee_user_id">
                                    <option value="">Sem responsavel</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= (int) $member['id'] ?>" <?= (int) ($task['assignee_user_id'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $member['name']) ?> (@<?= htmlspecialchars((string) $member['username']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="due_date" value="<?= htmlspecialchars((string) ($task['due_date'] ?? '')) ?>">
                                <div class="full">
                                    <textarea name="description" rows="2" placeholder="Descricao"><?= htmlspecialchars((string) ($task['description'] ?? '')) ?></textarea>
                                </div>
                                <div class="actions-inline full">
                                    <button type="submit">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($tasksByStatus[$status])): ?>
                    <p class="muted small">Sem tarefas.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <form id="kanbanMoveForm" method="post" action="index.php?r=commercial.kanban.task.move" class="hidden">
        <input type="hidden" name="task_id" id="kanbanMoveTaskId" value="">
        <input type="hidden" name="to_status" id="kanbanMoveStatus" value="">
    </form>
<?php endif; ?>
