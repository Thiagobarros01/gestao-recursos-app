<?php
$client = $client ?? [];
$sales = $sales ?? [];
$contacts = $contacts ?? [];
$activeTab = (string) ($activeTab ?? 'compras');
$clientId = (int) ($client['id'] ?? 0);
$tabs = [
    'compras' => 'Historico de Compras',
    'observacoes' => 'Observacoes',
];
?>

<section class="page-head">
    <div class="page-head-actions">
        <div>
            <h1><?= htmlspecialchars((string) ($client['client_name'] ?? 'Cliente')) ?></h1>
            <p>
                <?= htmlspecialchars((string) (($client['phone'] ?? '') !== '' ? $client['phone'] : '-')) ?>
                <?php if (!empty($client['owner_name'])): ?> - Vendedor: <?= htmlspecialchars((string) $client['owner_name']) ?><?php endif; ?>
            </p>
        </div>
        <div class="actions-inline">
            <a class="btn btn-muted" href="index.php?r=commercial.crm">Voltar</a>
            <button type="button" class="btn" data-toggle-target="quickSaleBox">Registrar venda rapida</button>
        </div>
    </div>
</section>

<?php if ((string) ($success ?? '') === '2'): ?>
    <div class="alert success">Compra registrada com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '4'): ?>
    <div class="alert success">Observacao registrada com sucesso.</div>
<?php endif; ?>
<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Preencha os dados obrigatorios.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel salvar no momento.</div>
<?php elseif ((string) ($error ?? '') === '3'): ?>
    <div class="alert error">Sem permissao para esta acao.</div>
<?php endif; ?>

<section class="stats-grid crm-client-summary">
    <article class="stat-card"><p>Total gasto</p><h3>R$ <?= number_format((float) ($client['total_spent'] ?? 0), 2, ',', '.') ?></h3></article>
    <article class="stat-card"><p>Compras</p><h3><?= (int) ($client['purchase_count'] ?? 0) ?></h3></article>
    <article class="stat-card"><p>Ticket medio</p><h3>R$ <?= number_format((float) ($client['ticket_avg'] ?? 0), 2, ',', '.') ?></h3></article>
    <article class="stat-card"><p>Status</p><h3 class="stat-small"><?= htmlspecialchars((string) ($client['status_customer'] ?? '-')) ?></h3></article>
    <article class="stat-card"><p>Ultima compra</p><h3 class="stat-small"><?= htmlspecialchars((string) ($client['last_purchase_date'] ?? '-')) ?></h3></article>
</section>

<section id="quickSaleBox" class="card quick-box hidden">
    <div class="card-headline">
        <h2>Registrar venda rapida</h2>
        <p>Fluxo operacional de balcao: poucos campos e registro imediato.</p>
    </div>
    <form method="post" action="index.php?r=commercial.crm.sale.store" class="form-grid four">
        <input type="hidden" name="client_id" value="<?= $clientId ?>">
        <input type="hidden" name="return_client" value="<?= $clientId ?>">
        <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" required>
        <input type="text" name="amount" placeholder="Valor da compra" required>
        <select name="payment_method">
            <option value="">Forma de pagamento</option>
            <option value="Pix">Pix</option>
            <option value="Cartao">Cartao</option>
            <option value="Dinheiro">Dinheiro</option>
            <option value="Boleto">Boleto</option>
            <option value="Outro">Outro</option>
        </select>
        <input type="text" name="order_number" placeholder="Numero pedido (opcional)">
        <input type="text" name="invoice_number" placeholder="Nota fiscal (opcional)">
        <input type="text" name="products_text" placeholder="Produtos (codigos/nomes)" class="full">
        <div class="full">
            <input type="text" name="notes" placeholder="Observacao (opcional)">
        </div>
        <div class="actions-inline full">
            <button type="submit">Salvar venda</button>
        </div>
    </form>
</section>

<section class="card">
    <nav class="tab-nav" aria-label="Abas do cliente CRM">
        <?php foreach ($tabs as $tabKey => $tabLabel): ?>
            <a class="tab-link <?= $activeTab === $tabKey ? 'active' : '' ?>" href="index.php?r=commercial.crm.client&id=<?= $clientId ?>&tab=<?= urlencode($tabKey) ?>">
                <?= htmlspecialchars($tabLabel) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($activeTab === 'compras'): ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Forma de pagamento</th>
                    <th>Pedido</th>
                    <th>Nota fiscal</th>
                    <th>Produtos</th>
                    <th>Observacao</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($sale['sale_date'] ?? '-')) ?></td>
                        <td>R$ <?= number_format((float) ($sale['amount'] ?? 0), 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string) (($sale['payment_method'] ?? '') !== '' ? $sale['payment_method'] : '-')) ?></td>
                        <td><?= htmlspecialchars((string) (($sale['order_number'] ?? '') !== '' ? $sale['order_number'] : '-')) ?></td>
                        <td><?= htmlspecialchars((string) (($sale['invoice_number'] ?? '') !== '' ? $sale['invoice_number'] : '-')) ?></td>
                        <td><?= htmlspecialchars((string) (($sale['products_text'] ?? '') !== '' ? $sale['products_text'] : '-')) ?></td>
                        <td><?= htmlspecialchars((string) (($sale['notes'] ?? '') !== '' ? $sale['notes'] : '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="7">Nenhuma compra registrada.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-grid two-col-equal">
            <article class="card card-inner">
                <div class="card-headline">
                    <h2>Nova observacao</h2>
                    <p>Anotacao interna para atendimento rapido.</p>
                </div>
                <form method="post" action="index.php?r=commercial.crm.contact.store" class="form-grid two">
                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                    <input type="hidden" name="return_client" value="<?= $clientId ?>">
                    <input type="hidden" name="contact_type" value="Observacao">
                    <div class="full">
                        <textarea name="notes" rows="4" placeholder="Digite a observacao interna"></textarea>
                    </div>
                    <div class="actions-inline full">
                        <button type="submit">Salvar observacao</button>
                    </div>
                </form>
            </article>

            <article class="card card-inner">
                <div class="card-headline">
                    <h2>Historico de observacoes</h2>
                    <p>Inclui anotacoes internas e registros anteriores.</p>
                </div>
                <ul class="timeline-list">
                    <?php foreach ($contacts as $contact): ?>
                        <li class="timeline-item">
                            <p class="timeline-head">
                                <strong><?= htmlspecialchars((string) (($contact['contact_type'] ?? '') !== '' ? $contact['contact_type'] : 'Observacao')) ?></strong>
                                <span><?= htmlspecialchars((string) ($contact['contact_date'] ?? '-')) ?></span>
                            </p>
                            <p class="timeline-body"><?= nl2br(htmlspecialchars((string) (($contact['notes'] ?? '') !== '' ? $contact['notes'] : 'Sem detalhes.'))) ?></p>
                            <p class="timeline-meta"><span>Por <?= htmlspecialchars((string) ($contact['user_name'] ?? '')) ?></span></p>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($contacts)): ?>
                        <li class="timeline-item"><p class="timeline-body">Nenhuma observacao registrada.</p></li>
                    <?php endif; ?>
                </ul>
            </article>
        </div>

        <article class="card card-inner">
            <div class="card-headline">
                <h2>Dados cadastrais</h2>
                <p>Resumo essencial para atendimento.</p>
            </div>
            <dl class="detail-list">
                <div><dt>Nome</dt><dd><?= htmlspecialchars((string) ($client['client_name'] ?? '-')) ?></dd></div>
                <div><dt>Telefone</dt><dd><?= htmlspecialchars((string) (($client['phone'] ?? '') !== '' ? $client['phone'] : '-')) ?></dd></div>
                <div><dt>Email</dt><dd><?= htmlspecialchars((string) (($client['email'] ?? '') !== '' ? $client['email'] : '-')) ?></dd></div>
                <div><dt>Nascimento</dt><dd><?= htmlspecialchars((string) (($client['birth_date'] ?? '') !== '' ? $client['birth_date'] : '-')) ?></dd></div>
                <div><dt>Observacoes do cadastro</dt><dd><?= nl2br(htmlspecialchars((string) (($client['notes'] ?? '') !== '' ? $client['notes'] : '-'))) ?></dd></div>
            </dl>
        </article>
    <?php endif; ?>
</section>
