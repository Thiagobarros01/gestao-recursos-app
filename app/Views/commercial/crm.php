<?php
$followupDays = (int) ($settings['followup_after_days'] ?? 30);
$q = (string) ($filters['q'] ?? '');
$statusFilter = (string) ($filters['status'] ?? '');
$ownerFilter = (int) ($filters['owner_user_id'] ?? 0);
?>
<section class="page-head">
    <h1>CRM Comercial</h1>
    <p>Cadastro rapido de clientes, registro de vendas e reativacao por tempo sem compra.</p>
</section>

<?php if ((string) ($success ?? '') === '1'): ?>
    <div class="alert success">Cliente cadastrado.</div>
<?php elseif ((string) ($success ?? '') === '2'): ?>
    <div class="alert success">Venda registrada com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '3'): ?>
    <div class="alert success">Regra de reativacao atualizada.</div>
<?php endif; ?>
<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Preencha os dados obrigatorios.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel salvar no momento.</div>
<?php elseif ((string) ($error ?? '') === '3'): ?>
    <div class="alert error">Sem permissao para esta acao.</div>
<?php endif; ?>

<section class="card-grid">
    <article class="card">
        <h2>Painel de reativacao</h2>
        <p class="muted">
            Clientes sem comprar ha pelo menos <strong><?= $followupDays ?></strong> dias:
            <strong><?= count($followupClients) ?></strong>
        </p>
        <?php if ($canManageSettings): ?>
            <form method="post" action="index.php?r=commercial.crm.settings.update" class="inline-form">
                <input type="number" min="1" max="3650" name="followup_after_days" value="<?= $followupDays ?>" required>
                <button type="submit">Salvar dias de reativacao</button>
            </form>
        <?php endif; ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Empresa</th>
                    <th>Ultima compra</th>
                    <th>Dias sem comprar</th>
                    <th>Responsavel</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($followupClients as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($row['client_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['last_purchase_date'] ?? '')) ?></td>
                        <td><span class="status-dot warning"><?= (int) ($row['days_since_last_purchase'] ?? 0) ?> dias</span></td>
                        <td><?= htmlspecialchars((string) ($row['owner_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($followupClients)): ?>
                    <tr><td colspan="5">Nenhum cliente no tempo de reativacao.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <h2>Cadastro rapido de cliente</h2>
        <form method="post" action="index.php?r=commercial.crm.client.store" class="form-grid two">
            <input type="text" name="client_name" placeholder="Nome do cliente" required>
            <input type="text" name="company_name" placeholder="Empresa (opcional)">
            <input type="text" name="phone" placeholder="Telefone">
            <input type="text" name="whatsapp" placeholder="WhatsApp">
            <input type="email" name="email" placeholder="Email">
            <select name="status">
                <option value="ativo" selected>Ativo</option>
                <option value="prospect">Prospect</option>
                <option value="inativo">Inativo</option>
            </select>
            <?php if ($canSeeAll): ?>
                <select name="owner_user_id">
                    <option value="">Responsavel (padrao: voce)</option>
                    <?php foreach ($users as $userRow): ?>
                        <option value="<?= (int) $userRow['id'] ?>"><?= htmlspecialchars((string) $userRow['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <div class="full">
                <textarea name="notes" rows="2" placeholder="Observacoes (opcional)"></textarea>
            </div>
            <div class="actions-inline full">
                <button type="submit">Cadastrar cliente</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>Registro rapido de venda</h2>
        <form method="post" action="index.php?r=commercial.crm.sale.store" class="form-grid two">
            <select name="client_id" required>
                <option value="">Selecione o cliente</option>
                <?php foreach ($clientOptions as $client): ?>
                    <option value="<?= (int) $client['id'] ?>">
                        <?= htmlspecialchars((string) $client['client_name']) ?><?= !empty($client['company_name']) ? ' - ' . htmlspecialchars((string) $client['company_name']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" required>
            <input type="text" name="amount" placeholder="Valor da venda (ex: 1500,00)" required>
            <div class="full">
                <textarea name="notes" rows="2" placeholder="Observacoes da venda (opcional)"></textarea>
            </div>
            <div class="actions-inline full">
                <button type="submit">Registrar venda</button>
            </div>
        </form>
    </article>
</section>

<section class="card">
    <h2>Clientes</h2>
    <form method="get" action="index.php" class="form-grid four compact-form">
        <input type="hidden" name="r" value="commercial.crm">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nome, empresa, contato">
        <select name="status_filter">
            <option value="">Todos status</option>
            <option value="ativo" <?= $statusFilter === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="prospect" <?= $statusFilter === 'prospect' ? 'selected' : '' ?>>Prospect</option>
            <option value="inativo" <?= $statusFilter === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>
        <?php if ($canSeeAll): ?>
            <select name="owner_user_id">
                <option value="">Todos responsaveis</option>
                <?php foreach ($users as $userRow): ?>
                    <option value="<?= (int) $userRow['id'] ?>" <?= $ownerFilter === (int) $userRow['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $userRow['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <div class="actions-inline full">
            <button type="submit" class="btn btn-muted">Filtrar</button>
            <a class="btn btn-muted" href="index.php?r=commercial.crm">Limpar</a>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Cliente</th>
                <th>Empresa</th>
                <th>Status</th>
                <th>Ultima compra</th>
                <th>Dias sem compra</th>
                <th>Total vendas</th>
                <th>Total R$</th>
                <th>Responsavel</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($row['client_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['status'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['last_purchase_date'] ?? '')) ?></td>
                    <td>
                        <?php if (isset($row['days_since_last_purchase']) && $row['days_since_last_purchase'] !== null): ?>
                            <?= (int) $row['days_since_last_purchase'] ?> dias
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= (int) ($row['total_sales'] ?? 0) ?></td>
                    <td>R$ <?= number_format((float) ($row['total_amount'] ?? 0), 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars((string) ($row['owner_name'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($clients)): ?>
                <tr><td colspan="8">Nenhum cliente encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Vendas recentes</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Data</th>
                <th>Cliente</th>
                <th>Empresa</th>
                <th>Vendedor</th>
                <th>Valor</th>
                <th>Observacoes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($sale['sale_date'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($sale['client_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($sale['company_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($sale['seller_name'] ?? '')) ?></td>
                    <td>R$ <?= number_format((float) ($sale['amount'] ?? 0), 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars((string) ($sale['notes'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
                <tr><td colspan="6">Sem vendas registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

