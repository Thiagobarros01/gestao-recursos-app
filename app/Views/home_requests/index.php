<section class="page-head">
    <h1>Pedido para levar equipamento</h1>
    <p>Solicitacao, aceite de entrega e controle de retorno.</p>
</section>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Pedido criado com sucesso.</div>
<?php elseif ((string) $success === '2'): ?>
    <div class="alert success">Pedido aprovado e entrega autorizada.</div>
<?php elseif ((string) $success === '3'): ?>
    <div class="alert success">Pedido recusado.</div>
<?php elseif ((string) $success === '4'): ?>
    <div class="alert success">Retorno do equipamento registrado.</div>
<?php endif; ?>

<?php if ((string) $error === '1'): ?>
    <div class="alert error">Dados invalidos para esta operacao.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel concluir a operacao.</div>
<?php elseif ((string) $error === '3'): ?>
    <div class="alert error">Voce nao tem permissao para aprovar/recusar retornos.</div>
<?php endif; ?>

<section class="card">
    <h2>Novo pedido</h2>
    <form method="post" action="index.php?r=ti.home-requests.store" class="form-grid three">
        <div>
            <label>Equipamento</label>
            <select name="asset_id" required>
                <option value="">Selecione</option>
                <?php foreach ($assets as $asset): ?>
                    <option value="<?= (int) $asset['id'] ?>">
                        <?= htmlspecialchars((string) $asset['tag']) ?> - <?= htmlspecialchars((string) ($asset['category_name'] ?? $asset['type'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Solicitante</label>
            <select name="requester_staff_id" required>
                <option value="">Selecione</option>
                <?php foreach ($staff as $person): ?>
                    <option value="<?= (int) $person['id'] ?>"><?= htmlspecialchars((string) $person['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="full">
            <label>Motivo</label>
            <textarea name="reason" rows="3" placeholder="Ex: trabalho remoto, reuniao externa, plantao" required></textarea>
        </div>

        <button type="submit">Criar pedido</button>
    </form>
</section>

<section class="card">
    <h2>Pedidos registrados</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipamento</th>
                    <th>Solicitante</th>
                    <th>Status</th>
                    <th>Solicitado em</th>
                    <th>Retorno previsto</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <?php
                        $rawStatus = (string) $request['status'];
                        $statusLabel = match ($rawStatus) {
                            'pending' => 'pendente',
                            'approved' => 'em uso externo',
                            'rejected' => 'recusado',
                            'returned' => 'entregue',
                            default => $rawStatus,
                        };
                    ?>
                    <tr>
                        <td>#<?= (int) $request['id'] ?></td>
                        <td>
                            <?= htmlspecialchars((string) $request['asset_tag']) ?>
                            <?php if (!empty($request['asset_serial'])): ?>
                                <br><span class="muted small">Serial: <?= htmlspecialchars((string) $request['asset_serial']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) $request['requester_name']) ?></td>
                        <td><?= htmlspecialchars($statusLabel) ?></td>
                        <td><?= htmlspecialchars((string) $request['requested_at']) ?></td>
                        <td><?= htmlspecialchars((string) ($request['due_return_date'] ?? '')) ?></td>
                        <td>
                            <div class="actions-inline">
                                <?php if ($canApprove && (string) $request['status'] === 'pending'): ?>
                                    <form method="post" action="index.php?r=ti.home-requests.approve">
                                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                        <input type="date" name="due_return_date">
                                        <button type="submit">Aprovar</button>
                                    </form>
                                    <form method="post" action="index.php?r=ti.home-requests.reject">
                                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                        <button class="btn-danger" type="submit">Recusar</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($canApprove && (string) $request['status'] === 'approved'): ?>
                                    <form method="post" action="index.php?r=ti.home-requests.return">
                                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                        <input type="text" name="condition_in" placeholder="Estado no retorno" required>
                                        <button type="submit">Registrar retorno</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($request['reason'])): ?>
                                <p class="muted small">Motivo: <?= htmlspecialchars((string) $request['reason']) ?></p>
                            <?php endif; ?>
                            <?php if ($canApprove && (string) $request['status'] === 'pending'): ?>
                                <p class="muted small">Se nao preencher data de retorno, o fechamento sera manual.</p>
                            <?php endif; ?>
                            <?php if (!empty($request['document_text'])): ?>
                                <details>
                                    <summary>Documento padrao</summary>
                                    <pre class="doc-preview"><?= htmlspecialchars((string) $request['document_text']) ?></pre>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7">Nenhum pedido registrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
