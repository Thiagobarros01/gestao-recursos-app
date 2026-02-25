<section class="page-head">
    <h1>Pedidos Operadores</h1>
    <p>Solicitacoes de maquinas/equipamentos enviadas pelos operadores.</p>
</section>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Pedido atualizado com sucesso.</div>
<?php endif; ?>
<?php if ((string) $error === '1'): ?>
    <div class="alert error">Pedido invalido.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel atualizar o pedido.</div>
<?php endif; ?>

<section class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Operador</th>
                    <th>Motivo</th>
                    <th>Detalhes</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $request['requester_name']) ?></td>
                        <td><?= htmlspecialchars((string) $request['reason']) ?></td>
                        <td><?= htmlspecialchars((string) ($request['details'] ?? '')) ?></td>
                        <?php
                            $status = (string) ($request['status'] ?? 'pending');
                            $statusLabel = match ($status) {
                                'pending' => 'Pendente',
                                'approved' => 'Aprovado',
                                'rejected' => 'Recusado',
                                default => $status,
                            };
                            $statusClass = match ($status) {
                                'approved' => 'status-dot ok',
                                'rejected' => 'status-dot danger',
                                default => 'status-dot warning',
                            };
                        ?>
                        <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                        <td><?= htmlspecialchars((string) $request['created_at']) ?></td>
                        <td>
                            <?php if ((string) $request['status'] === 'pending'): ?>
                                <div class="actions-inline">
                                    <form method="post" action="index.php?r=ti.operator-requests.approve">
                                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                        <button type="submit">Aprovar</button>
                                    </form>
                                    <form method="post" action="index.php?r=ti.operator-requests.reject">
                                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                        <button type="submit" class="btn-danger">Recusar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="muted">Revisado por <?= htmlspecialchars((string) ($request['reviewed_by_name'] ?? '')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6">Sem pedidos no momento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
