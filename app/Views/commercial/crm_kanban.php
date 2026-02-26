<?php
$board = $board ?? ['pipeline' => [], 'reactivation' => []];
$pipeline = $board['pipeline'] ?? [];
$reactivation = $board['reactivation'] ?? [];

$pipelineColumns = [
    'SEM_ETAPA' => 'Sem etapa',
    'EM_NEGOCIACAO' => 'Em negociacao',
    'COMPRA_NAO_REALIZADA' => 'Compra nao realizada',
    'COMPRA_REALIZADA' => 'Compra realizada',
];
$reactivationColumns = [
    'D30' => 'Sem comprar 30+ dias',
    'D60' => 'Sem comprar 60+ dias',
    'D90' => 'Sem comprar 90+ dias',
];
?>

<section class="page-head">
    <div class="page-head-actions">
        <div>
            <h1>CRM Kanban (Operacional)</h1>
            <p>Visualizacao rapida por etapa comercial + colunas automaticas de reativacao por dias sem compra.</p>
        </div>
        <div class="actions-inline">
            <a class="btn btn-muted" href="index.php?r=commercial.crm">Voltar ao CRM Lista</a>
        </div>
    </div>
</section>

<?php if ((string) ($success ?? '') === '1'): ?>
    <div class="alert success">Etapa do cliente atualizada.</div>
<?php endif; ?>
<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Cliente invalido para mover no kanban.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel atualizar a etapa no momento.</div>
<?php endif; ?>

<section class="card">
    <div class="card-headline">
        <h2>Pipeline CRM</h2>
        <p>Use para acompanhar atendimento do dia. A etapa e manual; reativacao abaixo e automatica por compras.</p>
    </div>
    <div class="kanban-board crm-kanban-board" data-readonly="1">
        <?php foreach ($pipelineColumns as $columnKey => $columnLabel): ?>
            <?php $items = $pipeline[$columnKey] ?? []; ?>
            <div class="kanban-column">
                <div class="kanban-head">
                    <h3><?= htmlspecialchars($columnLabel) ?></h3>
                    <span class="status-dot"><?= count($items) ?></span>
                </div>
                <?php foreach ($items as $row): ?>
                    <article class="kanban-card">
                        <p class="kanban-title"><?= htmlspecialchars((string) ($row['client_name'] ?? '')) ?></p>
                        <p class="small muted"><?= htmlspecialchars((string) (($row['erp_customer_code'] ?? '') !== '' ? $row['erp_customer_code'] : 'Sem cod ERP')) ?></p>
                        <p class="small"><?= htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : '-')) ?></p>
                        <p class="small muted">Ultima compra: <?= htmlspecialchars((string) (($row['last_purchase_date'] ?? '') !== '' ? $row['last_purchase_date'] : '-')) ?></p>
                        <p class="small muted">Dias sem comprar: <?= $row['days_without_purchase'] !== null ? (int) $row['days_without_purchase'] : '-' ?></p>
                        <div class="actions-inline">
                            <a class="btn btn-muted" href="index.php?r=commercial.crm.client&id=<?= (int) $row['id'] ?>">Ver</a>
                        </div>
                        <form method="post" action="index.php?r=commercial.crm.kanban.stage.update" class="form-grid">
                            <input type="hidden" name="client_id" value="<?= (int) $row['id'] ?>">
                            <select name="stage">
                                <?php foreach ($pipelineColumns as $targetKey => $targetLabel): ?>
                                    <option value="<?= htmlspecialchars($targetKey) ?>" <?= $targetKey === $columnKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($targetLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-muted">Mover</button>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <p class="small muted">Sem clientes nesta etapa.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="card">
    <div class="card-headline">
        <h2>Reativacao automatica</h2>
        <p>Colunas calculadas pelos dias sem comprar. Ideal para acao diaria da equipe.</p>
    </div>
    <div class="kanban-board crm-kanban-board reactivation-board" data-readonly="1">
        <?php foreach ($reactivationColumns as $columnKey => $columnLabel): ?>
            <?php $items = $reactivation[$columnKey] ?? []; ?>
            <div class="kanban-column">
                <div class="kanban-head">
                    <h3><?= htmlspecialchars($columnLabel) ?></h3>
                    <span class="status-dot warning"><?= count($items) ?></span>
                </div>
                <?php foreach ($items as $row): ?>
                    <article class="kanban-card">
                        <p class="kanban-title"><?= htmlspecialchars((string) ($row['client_name'] ?? '')) ?></p>
                        <p class="small muted"><?= htmlspecialchars((string) (($row['erp_customer_code'] ?? '') !== '' ? $row['erp_customer_code'] : 'Sem cod ERP')) ?></p>
                        <p class="small"><?= htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : '-')) ?></p>
                        <p class="small">Status CRM: <?= htmlspecialchars((string) ($row['status_customer'] ?? '-')) ?></p>
                        <p class="small">Dias sem comprar: <strong><?= $row['days_without_purchase'] !== null ? (int) $row['days_without_purchase'] : '-' ?></strong></p>
                        <div class="actions-inline">
                            <a class="btn btn-muted" href="index.php?r=commercial.crm.client&id=<?= (int) $row['id'] ?>">Abrir cliente</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <p class="small muted">Sem clientes nesta faixa.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
