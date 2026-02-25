<?php
$statusLabels = $stageLabels ?? [
    'todo' => 'Prospeccao',
    'doing' => 'Contato',
    'review' => 'Proposta',
    'done' => 'Fechado',
];
$q = (string) ($filters['query'] ?? '');
$priorityFilter = (string) ($filters['priority'] ?? '');
$assigneeFilter = (int) ($filters['assignee_user_id'] ?? 0);
$onlyOverdue = (int) ($filters['only_overdue'] ?? 0) === 1;
$dueToday = (int) ($filters['due_today'] ?? 0) === 1;
$onlyMine = (int) ($filters['only_mine'] ?? 0) === 1;
$boardIsArchived = $selectedBoard && (int) ($selectedBoard['is_archived'] ?? 0) === 1;
$currentUserId = (int) (\App\Core\Auth::user()['id'] ?? 0);
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
<?php elseif ((string) ($success ?? '') === '8'): ?>
    <div class="alert success">Quadro arquivado.</div>
<?php elseif ((string) ($success ?? '') === '9'): ?>
    <div class="alert success">Quadro reativado.</div>
<?php elseif ((string) ($success ?? '') === '10'): ?>
    <div class="alert success">Quadro excluido.</div>
<?php elseif ((string) ($success ?? '') === '11'): ?>
    <div class="alert success">Comentario salvo.</div>
<?php elseif ((string) ($success ?? '') === '12'): ?>
    <div class="alert success">Comentario removido.</div>
<?php endif; ?>

<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Dados invalidos para operacao.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel concluir a operacao.</div>
<?php elseif ((string) ($error ?? '') === '3'): ?>
    <div class="alert error">Sem permissao para esta acao.</div>
<?php elseif ((string) ($error ?? '') === '4'): ?>
    <div class="alert error">Quadro arquivado. Reative para editar tarefas.</div>
<?php endif; ?>

<section class="card-grid">
    <article class="card">
        <h2>Quadros</h2>
        <form method="get" action="index.php" class="actions-inline">
            <input type="hidden" name="r" value="commercial.kanban">
            <label class="permission-item">
                <input type="checkbox" name="show_archived" value="1" <?= !empty($showArchived) ? 'checked' : '' ?>>
                <span>Mostrar arquivados</span>
            </label>
            <button type="submit" class="btn btn-muted">Aplicar</button>
        </form>
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
                        <p class="muted small">
                            Tarefas: <?= (int) ($board['total_tasks'] ?? 0) ?> | Dono: <?= htmlspecialchars((string) ($board['owner_name'] ?? '')) ?>
                            <?php if ((int) ($board['is_archived'] ?? 0) === 1): ?>
                                | <span class="status-dot muted">Arquivado</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a class="btn btn-muted" href="index.php?r=commercial.kanban&board=<?= (int) $board['id'] ?><?= !empty($showArchived) ? '&show_archived=1' : '' ?>">Abrir</a>
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
                    <div class="actions-inline">
                        <button type="button" class="btn btn-muted" data-toggle-target="editBoardBox">Renomear quadro</button>
                        <?php if ((int) ($selectedBoard['is_archived'] ?? 0) === 1): ?>
                            <form method="post" action="index.php?r=commercial.kanban.board.unarchive">
                                <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                                <button type="submit">Reativar quadro</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="index.php?r=commercial.kanban.board.archive" onsubmit="return confirm('Arquivar este quadro?');">
                                <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                                <button type="submit" class="btn btn-muted">Arquivar quadro</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="index.php?r=commercial.kanban.board.delete" onsubmit="return confirm('Excluir quadro e todas as tarefas?');">
                            <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                            <button type="submit" class="btn-danger">Excluir quadro</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($selectedBoard['description'])): ?>
                <p class="muted"><?= htmlspecialchars((string) $selectedBoard['description']) ?></p>
            <?php endif; ?>

            <?php if ((int) ($selectedBoard['is_archived'] ?? 0) === 0): ?>
                <form method="get" action="index.php" class="form-grid four compact-form">
                    <input type="hidden" name="r" value="commercial.kanban">
                    <input type="hidden" name="board" value="<?= (int) $selectedBoard['id'] ?>">
                    <?php if (!empty($showArchived)): ?>
                        <input type="hidden" name="show_archived" value="1">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por cliente, empresa, tag ou tarefa">
                    <select name="priority_filter">
                        <option value="">Todas prioridades</option>
                        <?php foreach ($priorities as $priority): ?>
                            <option value="<?= htmlspecialchars((string) $priority) ?>" <?= $priorityFilter === (string) $priority ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ucfirst((string) $priority)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="assignee_user_id">
                        <option value="">Todos responsaveis</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= (int) $member['id'] ?>" <?= $assigneeFilter === (int) $member['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $member['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="actions-inline">
                        <label class="permission-item"><input type="checkbox" name="only_overdue" value="1" <?= $onlyOverdue ? 'checked' : '' ?>><span>Atrasadas</span></label>
                        <label class="permission-item"><input type="checkbox" name="due_today" value="1" <?= $dueToday ? 'checked' : '' ?>><span>Vence hoje</span></label>
                        <label class="permission-item"><input type="checkbox" name="only_mine" value="1" <?= $onlyMine ? 'checked' : '' ?>><span>Somente minhas</span></label>
                    </div>
                    <div class="actions-inline full">
                        <button type="submit" class="btn btn-muted">Aplicar filtros</button>
                        <a class="btn btn-muted" href="index.php?r=commercial.kanban&board=<?= (int) $selectedBoard['id'] ?><?= !empty($showArchived) ? '&show_archived=1' : '' ?>">Limpar</a>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($canManage && (int) ($selectedBoard['is_archived'] ?? 0) === 0): ?>
                <div id="editBoardBox" class="quick-box hidden">
                    <form method="post" action="index.php?r=commercial.kanban.board.update" class="form-grid two">
                        <input type="hidden" name="board_id" value="<?= (int) $selectedBoard['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars((string) $selectedBoard['name']) ?>" required>
                        <input type="text" name="description" value="<?= htmlspecialchars((string) ($selectedBoard['description'] ?? '')) ?>" placeholder="Descricao">
                        <input type="text" name="stage_name_todo" value="<?= htmlspecialchars((string) ($statusLabels['todo'] ?? 'Prospeccao')) ?>" placeholder="Nome da coluna 1">
                        <input type="text" name="stage_name_doing" value="<?= htmlspecialchars((string) ($statusLabels['doing'] ?? 'Contato')) ?>" placeholder="Nome da coluna 2">
                        <input type="text" name="stage_name_review" value="<?= htmlspecialchars((string) ($statusLabels['review'] ?? 'Proposta')) ?>" placeholder="Nome da coluna 3">
                        <input type="text" name="stage_name_done" value="<?= htmlspecialchars((string) ($statusLabels['done'] ?? 'Fechado')) ?>" placeholder="Nome da coluna 4">
                        <div class="actions-inline full">
                            <button type="submit">Salvar quadro e etapas</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($canManage && (int) ($selectedBoard['is_archived'] ?? 0) === 0): ?>
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
                        <label>Titulo da oportunidade</label>
                        <input type="text" name="title" placeholder="Ex: Renovacao contrato ACME" required>
                    </div>
                    <div>
                        <label>Cliente</label>
                        <input type="text" name="customer_name" placeholder="Nome do cliente">
                    </div>
                    <div>
                        <label>Empresa</label>
                        <input type="text" name="company_name" placeholder="Nome da empresa">
                    </div>
                    <div>
                        <label>Valor (R$)</label>
                        <input type="text" name="deal_value" placeholder="0,00">
                    </div>
                    <div>
                        <label>Etiqueta</label>
                        <input type="text" name="tag_name" placeholder="Ex: renovacao">
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
                                    <?= htmlspecialchars((string) ($statusLabels[$status] ?? (string) $status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Prazo</label>
                        <input type="date" name="due_date">
                    </div>
                    <div class="full">
                        <label>Descricao e proximo passo</label>
                        <textarea name="description" rows="3" placeholder="Detalhes da negociacao e proximo passo"></textarea>
                    </div>
                    <div class="actions-inline full">
                        <button type="submit">Criar oportunidade</button>
                    </div>
                </form>
            <?php elseif ((int) ($selectedBoard['is_archived'] ?? 0) === 1): ?>
                <p class="muted">Quadro arquivado. Reative para editar membros e tarefas.</p>
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
                <article class="stat-card"><p>Valor total em negociacao</p><h3>R$ <?= number_format((float) ($managerSummary['total_value'] ?? 0), 2, ',', '.') ?></h3></article>
                <article class="stat-card"><p><?= htmlspecialchars((string) ($statusLabels['todo'] ?? 'Prospeccao')) ?></p><h3><?= (int) $managerSummary['by_status']['todo'] ?></h3></article>
                <article class="stat-card"><p><?= htmlspecialchars((string) ($statusLabels['doing'] ?? 'Contato')) ?></p><h3><?= (int) $managerSummary['by_status']['doing'] ?></h3></article>
                <article class="stat-card"><p><?= htmlspecialchars((string) ($statusLabels['review'] ?? 'Proposta')) ?></p><h3><?= (int) $managerSummary['by_status']['review'] ?></h3></article>
                <article class="stat-card ok"><p><?= htmlspecialchars((string) ($statusLabels['done'] ?? 'Fechado')) ?></p><h3><?= (int) $managerSummary['by_status']['done'] ?></h3></article>
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

    <section class="kanban-board" data-board-id="<?= (int) $selectedBoard['id'] ?>" data-readonly="<?= $boardIsArchived ? '1' : '0' ?>">
        <?php foreach ($statuses as $status): ?>
            <article class="kanban-column <?= $status === 'done' ? 'done-col' : '' ?>" data-status="<?= htmlspecialchars((string) $status) ?>">
                <h3>
                    <?= htmlspecialchars((string) ($statusLabels[$status] ?? (string) $status)) ?>
                    <span class="muted small">(<?= count($tasksByStatus[$status]) ?>)</span>
                </h3>
                <?php foreach ($tasksByStatus[$status] as $task): ?>
                    <div class="kanban-card <?= (string) $task['status'] === 'done' ? 'done-card' : '' ?>" draggable="<?= $boardIsArchived ? 'false' : 'true' ?>" data-task-id="<?= (int) $task['id'] ?>">
                        <p class="kanban-title"><?= htmlspecialchars((string) $task['title']) ?></p>
                        <?php if (!empty($task['customer_name']) || !empty($task['company_name'])): ?>
                            <p class="muted small">
                                Cliente: <?= htmlspecialchars((string) ($task['customer_name'] ?? '-')) ?><br>
                                Empresa: <?= htmlspecialchars((string) ($task['company_name'] ?? '-')) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($task['description'])): ?>
                            <p class="muted small"><?= nl2br(htmlspecialchars((string) $task['description'])) ?></p>
                        <?php endif; ?>
                        <p class="muted small">
                            Prioridade: <?= htmlspecialchars((string) $task['priority']) ?><br>
                            <?php if (!empty($task['deal_value'])): ?>
                                Valor: R$ <?= number_format((float) $task['deal_value'], 2, ',', '.') ?><br>
                            <?php endif; ?>
                            <?php if (!empty($task['tag_name'])): ?>
                                Etiqueta: <?= htmlspecialchars((string) $task['tag_name']) ?><br>
                            <?php endif; ?>
                            Responsavel: <?= htmlspecialchars((string) ($task['assignee_name'] ?? 'Nao definido')) ?><br>
                            <?php if (!empty($task['due_date'])): ?>
                                Prazo: <?= htmlspecialchars((string) $task['due_date']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="actions-inline">
                            <button type="button" class="btn btn-muted" data-toggle-target="editTask<?= (int) $task['id'] ?>">Editar</button>
                            <?php if (!$boardIsArchived): ?>
                                <?php foreach ($statuses as $moveStatus): ?>
                                    <?php if ($moveStatus === (string) $task['status']): ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <form method="post" action="index.php?r=commercial.kanban.task.move">
                                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                        <input type="hidden" name="to_status" value="<?= htmlspecialchars((string) $moveStatus) ?>">
                                        <button type="submit" class="btn btn-muted"><?= htmlspecialchars((string) ($statusLabels[$moveStatus] ?? (string) $moveStatus)) ?></button>
                                    </form>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                                <input type="text" name="customer_name" value="<?= htmlspecialchars((string) ($task['customer_name'] ?? '')) ?>" placeholder="Cliente">
                                <input type="text" name="company_name" value="<?= htmlspecialchars((string) ($task['company_name'] ?? '')) ?>" placeholder="Empresa">
                                <input type="text" name="deal_value" value="<?= isset($task['deal_value']) && $task['deal_value'] !== null ? htmlspecialchars((string) $task['deal_value']) : '' ?>" placeholder="Valor">
                                <input type="text" name="tag_name" value="<?= htmlspecialchars((string) ($task['tag_name'] ?? '')) ?>" placeholder="Etiqueta">
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
                        <div class="quick-box">
                            <div class="kanban-comment-head">
                                <strong>Comentarios</strong>
                                <span class="muted small"><?= (int) ($task['comments_count'] ?? 0) ?></span>
                            </div>
                            <form method="post" action="index.php?r=commercial.kanban.comment.store" class="inline-form">
                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                <input type="text" name="comment_text" placeholder="Adicionar comentario" required>
                                <button type="submit" class="btn btn-muted">Comentar</button>
                            </form>
                            <?php foreach (($commentsByTaskId[(int) $task['id']] ?? []) as $comment): ?>
                                <div class="kanban-comment-item">
                                    <p class="muted small">
                                        <strong><?= htmlspecialchars((string) ($comment['user_name'] ?? '')) ?></strong>
                                        (<?= htmlspecialchars((string) ($comment['created_at'] ?? '')) ?>)
                                    </p>
                                    <p><?= htmlspecialchars((string) ($comment['comment_text'] ?? '')) ?></p>
                                    <?php if ($canManage || (int) ($comment['user_id'] ?? 0) === $currentUserId): ?>
                                        <form method="post" action="index.php?r=commercial.kanban.comment.delete">
                                            <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                            <button type="submit" class="btn btn-muted">Excluir comentario</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
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
