<section class="page-head">
    <h1>Modulo Vendedor</h1>
    <p>Solicite equipamento para casa e informe produtos em falta para Compras.</p>
</section>

<section class="card">
    <p class="muted">
        Fluxo: <strong>Vendedor</strong> abre pedido -> <strong>Compras/TI</strong> analisam -> status volta atualizado aqui.
    </p>
</section>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Alerta de produto em falta enviado para Compras.</div>
<?php elseif ((string) $success === '2'): ?>
    <div class="alert success">Solicitacao enviada para TI.</div>
<?php elseif ((string) $success === '3'): ?>
    <div class="alert success">Esse codigo de produto ja estava aberto em falta. Nenhum duplicado foi criado.</div>
<?php endif; ?>
<?php if ((string) $error === '1'): ?>
    <div class="alert error">Preencha os dados obrigatorios.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel salvar agora.</div>
<?php endif; ?>

<section class="card-grid">
    <article class="card">
        <h2>Produtos em falta</h2>
        <form method="post" action="index.php?r=commercial.seller.shortage.store" class="form-grid two">
            <input type="text" name="product_code" placeholder="Codigo interno do produto (obrigatorio)" required>
            <input type="text" name="product_name" placeholder="Nome do produto (opcional)">
            <select name="priority">
                <option value="alta" selected>Prioridade: Alta (padrao)</option>
                <?php foreach ($priorities as $priority): ?>
                    <option value="<?= htmlspecialchars((string) $priority) ?>"><?= htmlspecialchars((string) ucfirst((string) $priority)) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="full">
                <textarea name="details" rows="3" placeholder="Descricao (opcional)"></textarea>
            </div>
            <div class="actions-inline full">
                <button type="submit">Enviar para Compras</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>Pedido para levar equipamento para casa</h2>
        <form method="post" action="index.php?r=commercial.seller.ti-request.store" class="form-grid two">
            <select name="reason" required>
                <option value="">Motivo</option>
                <option value="Levar equipamento para casa" selected>Levar equipamento para casa</option>
                <option value="Equipamento novo">Equipamento novo</option>
                <option value="Troca de equipamento">Troca de equipamento</option>
                <option value="Manutencao">Manutencao</option>
            </select>
            <div class="full">
                <textarea name="details" rows="3" placeholder="Detalhes da solicitacao"></textarea>
            </div>
            <div class="actions-inline full">
                <button type="submit">Enviar para TI</button>
            </div>
        </form>
    </article>
</section>

<section class="card">
    <h2>Meus alertas de falta</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Produto</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Criado em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) ($row['product_code'] ?? '')) ?></strong></td>
                        <td><?= htmlspecialchars((string) $row['product_name']) ?></td>
                        <td><?= htmlspecialchars((string) $row['priority']) ?></td>
                        <?php
                            $status = (string) ($row['status'] ?? 'pending');
                            $statusLabel = match ($status) {
                                'pending' => 'Pendente',
                                'accepted' => 'Em atendimento',
                                'resolved' => 'Atendido',
                                'closed' => 'Fechado',
                                default => $status,
                            };
                            $statusClass = match ($status) {
                                'pending' => 'status-dot warning',
                                'accepted' => 'status-dot info',
                                'resolved' => 'status-dot ok',
                                'closed' => 'status-dot muted',
                                default => 'status-dot',
                            };
                        ?>
                        <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                        <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($alerts)): ?>
                    <tr><td colspan="5">Sem alertas enviados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Minhas solicitacoes para TI</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Motivo</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Revisado por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tiRequests as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['reason']) ?></td>
                        <?php
                            $status = (string) ($row['status'] ?? 'pending');
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
                        <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                        <td><?= htmlspecialchars((string) ($row['reviewed_by_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($tiRequests)): ?>
                    <tr><td colspan="4">Sem solicitacoes enviadas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
