<section class="page-head">
    <h1>Gerenciamento de Compras</h1>
    <p>Controle de produtos, faltas sinalizadas pelo Comercial e historico por periodo.</p>
</section>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Produto cadastrado.</div>
<?php elseif ((string) $success === '2'): ?>
    <div class="alert success">Estoque atualizado.</div>
<?php elseif ((string) $success === '3'): ?>
    <div class="alert success">Pedido marcado em atendimento.</div>
<?php elseif ((string) $success === '4'): ?>
    <div class="alert success">Observacao marcada como atendida.</div>
<?php elseif ((string) $success === '5'): ?>
    <div class="alert success">Registro fechado com sucesso.</div>
<?php elseif ((string) $success === '6'): ?>
    <div class="alert success">Todos os registros visiveis foram marcados como atendidos.</div>
<?php elseif ((string) $success === '7'): ?>
    <div class="alert success">Todos os registros visiveis foram fechados.</div>
<?php endif; ?>
<?php if ((string) $error === '1'): ?>
    <div class="alert error">Dados invalidos.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel concluir a operacao.</div>
<?php endif; ?>

<?php
    $statusFilter = (string) ($filters['status'] ?? '');
    $dateFrom = (string) ($filters['date_from'] ?? '');
    $dateTo = (string) ($filters['date_to'] ?? '');
    $month = (string) ($filters['month'] ?? '');
    $mine = (int) ($filters['mine'] ?? 0);

    $statusLabel = static function (string $status): string {
        return match ($status) {
            'pending' => 'Pendente',
            'accepted' => 'Em atendimento',
            'resolved' => 'Atendido',
            'closed' => 'Fechado',
            default => $status,
        };
    };

    $statusClass = static function (string $status): string {
        return match ($status) {
            'pending' => 'status-dot warning',
            'accepted' => 'status-dot info',
            'resolved' => 'status-dot ok',
            'closed' => 'status-dot muted',
            default => 'status-dot',
        };
    };
?>

<section class="card">
    <h2>Produtos</h2>
    <form method="post" action="index.php?r=purchases.products.store" class="form-grid four">
        <input type="text" name="name" placeholder="Nome do produto" required>
        <input type="text" name="sku" placeholder="SKU (opcional)">
        <input type="number" name="stock_qty" placeholder="Estoque atual" min="0" value="0">
        <input type="number" name="min_qty" placeholder="Estoque minimo" min="0" value="0">
        <div class="actions-inline full">
            <button type="submit">Cadastrar produto</button>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>SKU</th>
                    <th>Atual</th>
                    <th>Minimo</th>
                    <th>Status</th>
                    <th>Ajustar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['name']) ?></td>
                        <td><?= htmlspecialchars((string) ($row['sku'] ?? '')) ?></td>
                        <td><?= (int) $row['stock_qty'] ?></td>
                        <td><?= (int) $row['min_qty'] ?></td>
                        <td><?= (int) $row['is_shortage'] === 1 ? 'Em falta' : 'Ok' ?></td>
                        <td>
                            <form method="post" action="index.php?r=purchases.products.update-stock" class="actions-inline">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <input type="number" name="stock_qty" min="0" value="<?= (int) $row['stock_qty'] ?>">
                                <input type="number" name="min_qty" min="0" value="<?= (int) $row['min_qty'] ?>">
                                <button type="submit">Salvar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6">Sem produtos cadastrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Produtos em falta (alertas dos vendedores)</h2>

    <form method="get" action="index.php" class="form-grid four compact-form">
        <input type="hidden" name="r" value="purchases.manage">
        <div>
            <label>Status</label>
            <select name="status_filter">
                <option value="">Todos</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pendente</option>
                <option value="accepted" <?= $statusFilter === 'accepted' ? 'selected' : '' ?>>Em atendimento</option>
                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Atendido</option>
                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Fechado</option>
            </select>
        </div>
        <div>
            <label>Data inicial</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div>
            <label>Data final</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div>
            <label>Mes</label>
            <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        </div>
        <div>
            <label>&nbsp;</label>
            <label class="permission-item">
                <input type="checkbox" name="mine" value="1" <?= $mine === 1 ? 'checked' : '' ?>>
                <span>Somente marcados para mim</span>
            </label>
        </div>
        <div class="actions-inline full">
            <button type="submit">Aplicar filtros</button>
            <a class="btn btn-muted" href="index.php?r=purchases.manage">Limpar filtros</a>
        </div>
    </form>

    <form method="post" action="index.php?r=purchases.shortages.resolve-all" class="form-grid three compact-form">
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
        <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
        <input type="hidden" name="mine" value="<?= $mine === 1 ? '1' : '0' ?>">
        <input type="text" name="resolution_note" placeholder="Observacao para lote (opcional)">
        <div class="actions-inline full">
            <button type="submit">Marcar todos como atendido</button>
        </div>
    </form>

    <form method="post" action="index.php?r=purchases.shortages.close-all" class="form-grid three compact-form" onsubmit="return confirm('Deseja fechar todos os registros visiveis?');">
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
        <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
        <input type="hidden" name="mine" value="<?= $mine === 1 ? '1' : '0' ?>">
        <input type="text" name="resolution_note" placeholder="Motivo do fechamento (opcional)">
        <div class="actions-inline full">
            <button type="submit" class="btn-danger">Fechar todos</button>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Produto</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Solicitante</th>
                    <th>Detalhes</th>
                    <th>Historico</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) ($alert['product_code'] ?? '')) ?></strong></td>
                        <td><?= htmlspecialchars((string) $alert['product_name']) ?></td>
                        <td><?= htmlspecialchars((string) $alert['priority']) ?></td>
                        <td><span class="<?= $statusClass((string) $alert['status']) ?>"><?= htmlspecialchars($statusLabel((string) $alert['status'])) ?></span></td>
                        <td><?= htmlspecialchars((string) $alert['requester_name']) ?></td>
                        <td><?= htmlspecialchars((string) ($alert['details'] ?? '')) ?></td>
                        <td>
                            <span class="muted small">
                                Criado: <?= htmlspecialchars((string) ($alert['created_at'] ?? '')) ?><br>
                                <?php if (!empty($alert['accepted_at'])): ?>
                                    Em atendimento por <?= htmlspecialchars((string) ($alert['accepted_by_name'] ?? '')) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($alert['resolved_at'])): ?>
                                    Atendido por <?= htmlspecialchars((string) ($alert['resolved_by_name'] ?? '')) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($alert['closed_at'])): ?>
                                    Fechado por <?= htmlspecialchars((string) ($alert['closed_by_name'] ?? '')) ?>
                                <?php endif; ?>
                                <?php if (!empty($alert['resolution_note'])): ?>
                                    <br>Obs: <?= htmlspecialchars((string) $alert['resolution_note']) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((string) $alert['status'] === 'pending'): ?>
                                <form method="post" action="index.php?r=purchases.shortages.accept">
                                    <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
                                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
                                    <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                    <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                    <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                                    <input type="hidden" name="mine" value="<?= $mine === 1 ? '1' : '0' ?>">
                                    <button type="submit">Pedido aceito</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array((string) $alert['status'], ['pending', 'accepted'], true)): ?>
                                <form method="post" action="index.php?r=purchases.shortages.resolve" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
                                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
                                    <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                    <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                    <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                                    <input type="hidden" name="mine" value="<?= $mine === 1 ? '1' : '0' ?>">
                                    <input type="text" name="resolution_note" placeholder="Observacao atendida (opcional)">
                                    <button type="submit" class="btn-muted">Observacao atendida</button>
                                </form>
                            <?php endif; ?>

                            <?php if ((string) $alert['status'] !== 'closed'): ?>
                                <form method="post" action="index.php?r=purchases.shortages.close" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
                                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
                                    <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                    <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                    <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                                    <input type="hidden" name="mine" value="<?= $mine === 1 ? '1' : '0' ?>">
                                    <input type="text" name="resolution_note" placeholder="Motivo do fechamento (opcional)">
                                    <button type="submit" class="btn-danger">Fechar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($alerts)): ?>
                    <tr><td colspan="8">Sem alertas para os filtros selecionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
