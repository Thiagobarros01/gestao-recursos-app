<?php
$filters = $filters ?? [];
$statusCounts = $statusCounts ?? ['ATIVO' => 0, 'INATIVO' => 0, 'VIP' => 0, 'NOVO' => 0];

$clientCodeFilter = (string) ($filters['client_code'] ?? '');
$clientNameFilter = (string) ($filters['client_name'] ?? '');
$mainSearch = (string) ($filters['main_search'] ?? '');
$statusCustomerFilter = (string) ($filters['status_customer'] ?? '');
$neighborhoodFilter = (string) ($filters['neighborhood'] ?? '');
$minTotalSpentFilter = (string) ($filters['min_total_spent'] ?? '');
$maxTotalSpentFilter = (string) ($filters['max_total_spent'] ?? '');
$ownerFilter = (int) ($filters['owner_user_id'] ?? 0);
$searchHasValue = $mainSearch !== '' || $clientCodeFilter !== '' || $clientNameFilter !== '';
$existingClientId = (int) ($_GET['existing_client_id'] ?? 0);
$clientPagination = $clientPagination ?? ['page' => 1, 'per_page' => 25, 'total_items' => 0, 'total_pages' => 1];
$currentPage = max(1, (int) ($clientPagination['page'] ?? 1));
$totalPages = max(1, (int) ($clientPagination['total_pages'] ?? 1));
$totalItems = max(0, (int) ($clientPagination['total_items'] ?? 0));

$buildCrmUrl = static function (array $changes = []) use ($filters): string {
    $query = ['r' => 'commercial.crm'];
    $current = [
        'client_code_filter' => (string) ($filters['client_code'] ?? ''),
        'client_name_filter' => (string) ($filters['client_name'] ?? ''),
        'main_search' => (string) ($filters['main_search'] ?? ''),
        'status_filter' => (string) ($filters['status_customer'] ?? ''),
        'neighborhood_filter' => (string) ($filters['neighborhood'] ?? ''),
        'min_total_spent' => (string) ($filters['min_total_spent'] ?? ''),
        'max_total_spent' => (string) ($filters['max_total_spent'] ?? ''),
        'owner_user_id' => (string) ((int) ($filters['owner_user_id'] ?? 0)),
        'page' => (string) max(1, (int) ($filters['page'] ?? 1)),
    ];

    foreach ($current as $key => $value) {
        if ($value !== '' && $value !== '0' && !($key === 'page' && $value === '1')) {
            $query[$key] = $value;
        }
    }
    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        $query[$key] = (string) $value;
    }

    return 'index.php?' . http_build_query($query);
};
?>
<section class="page-head">
    <div class="page-head-actions">
        <div>
            <h1>CRM Lite B2C</h1>
            <p>Fluxo operacional rapido para varejo: buscar cliente, consultar historico e agir.</p>
        </div>
        <div class="actions-inline">
            <a class="btn btn-muted" href="<?= htmlspecialchars($buildCrmUrl(['status_filter' => 'INATIVO'])) ?>">Reativacao</a>
            <a class="btn btn-muted" href="index.php?r=commercial.crm.kanban">CRM Kanban</a>
            <?php if (!empty($canManageSettings)): ?>
                <a class="btn btn-muted" href="index.php?r=settings#crm-config">Configuracao CRM</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ((string) ($success ?? '') === '1'): ?>
    <div class="alert success">Cliente cadastrado.</div>
<?php elseif ((string) ($success ?? '') === '2'): ?>
    <div class="alert success">Compra registrada com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '4'): ?>
    <div class="alert success">Observacao registrada com sucesso.</div>
<?php elseif ((string) ($success ?? '') === '5'): ?>
    <div class="alert success">
        Importacao concluida (<?= htmlspecialchars((string) ($_GET['import_type'] ?? 'csv')) ?>):
        criados <?= (int) ($_GET['created'] ?? 0) ?>,
        atualizados <?= (int) ($_GET['updated'] ?? 0) ?>,
        compras <?= (int) ($_GET['sales'] ?? 0) ?>,
        ignorados <?= (int) ($_GET['skipped'] ?? 0) ?>,
        falhas <?= (int) ($_GET['failed'] ?? 0) ?>.
    </div>
<?php endif; ?>
<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Preencha os dados obrigatorios.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel salvar no momento.</div>
<?php elseif ((string) ($error ?? '') === '3'): ?>
    <div class="alert error">Sem permissao para esta acao.</div>
<?php elseif ((string) ($error ?? '') === '4'): ?>
    <div class="alert error">
        Telefone ja cadastrado.
        <?php if ($existingClientId > 0): ?>
            <a href="index.php?r=commercial.crm.client&id=<?= $existingClientId ?>">Abrir cadastro existente</a>
        <?php else: ?>
            Verifique se o cliente ja existe antes de cadastrar novamente.
        <?php endif; ?>
    </div>
<?php elseif ((string) ($error ?? '') === '5'): ?>
    <div class="alert error">Falha na importacao CSV. Verifique o arquivo, colunas e formato (CSV UTF-8 com cabecalho).</div>
<?php endif; ?>

<section class="card crm-search-card">
    <div class="card-headline">
        <h2>Busca rapida</h2>
        <p>Prioridade para telefone e nome. Use filtros abaixo apenas quando precisar refinar.</p>
    </div>
    <form method="get" action="index.php" class="form-grid four crm-search-grid" data-live-search-form="1">
        <input type="hidden" name="r" value="commercial.crm">
        <input
            type="text"
            name="main_search"
            value="<?= htmlspecialchars($mainSearch) ?>"
            placeholder="Buscar por telefone ou nome"
            data-live-search-input="phone"
            autofocus
        >
        <input type="hidden" name="client_code_filter" value="">
        <input type="hidden" name="client_name_filter" value="">
        <span class="muted helper-inline full">Busca prioriza telefone exato. Se nao houver, retorna por nome parcial.</span>
        <div class="actions-inline">
            <button type="submit">Buscar</button>
            <a class="btn btn-muted" href="index.php?r=commercial.crm">Limpar</a>
        </div>
        <?php if ($searchHasValue): ?>
            <p class="muted helper-inline full">Busca aplicada sobre a base atual. Clique em um cliente para abrir o detalhe.</p>
        <?php endif; ?>
    </form>
</section>

<section class="stats-grid">
    <a class="stat-card <?= $statusCustomerFilter === 'ATIVO' ? 'selected' : '' ?>" href="<?= htmlspecialchars($buildCrmUrl(['status_filter' => 'ATIVO', 'page' => 1])) ?>">
        <p>Clientes ativos</p>
        <h3><?= (int) ($statusCounts['ATIVO'] ?? 0) ?></h3>
    </a>
    <a class="stat-card danger <?= $statusCustomerFilter === 'INATIVO' ? 'selected' : '' ?>" href="<?= htmlspecialchars($buildCrmUrl(['status_filter' => 'INATIVO', 'page' => 1])) ?>">
        <p>Clientes inativos</p>
        <h3><?= (int) ($statusCounts['INATIVO'] ?? 0) ?></h3>
    </a>
    <a class="stat-card ok <?= $statusCustomerFilter === 'VIP' ? 'selected' : '' ?>" href="<?= htmlspecialchars($buildCrmUrl(['status_filter' => 'VIP', 'page' => 1])) ?>">
        <p>Clientes VIP</p>
        <h3><?= (int) ($statusCounts['VIP'] ?? 0) ?></h3>
    </a>
    <a class="stat-card warn <?= $statusCustomerFilter === 'NOVO' ? 'selected' : '' ?>" href="<?= htmlspecialchars($buildCrmUrl(['status_filter' => 'NOVO', 'page' => 1])) ?>">
        <p>Clientes novos</p>
        <h3><?= (int) ($statusCounts['NOVO'] ?? 0) ?></h3>
    </a>
</section>

<section class="card">
    <div class="table-head">
        <div class="card-headline">
            <h2>Importar dados (CSV)</h2>
            <p>Ideal para trazer base do ERP sem retrabalho da equipe. Importe clientes e compras em arquivos separados.</p>
        </div>
        <button type="button" class="btn btn-muted" data-toggle-target="crmImportBox">Abrir importacao</button>
    </div>
    <div id="crmImportBox" class="quick-box hidden">
        <form method="post" action="index.php?r=commercial.crm.import" enctype="multipart/form-data" class="form-grid three">
            <select name="import_type" required>
                <option value="clientes">Clientes (cadastro base)</option>
                <option value="compras">Compras (historico de vendas)</option>
            </select>
            <input type="file" name="csv_file" accept=".csv,text/csv" required>
            <div class="actions-inline">
                <button type="submit">Importar CSV</button>
            </div>
            <div class="full subtle-details">
                <div class="actions-inline">
                    <a class="btn btn-muted" href="index.php?r=commercial.crm.import.template&type=clientes">Baixar modelo clientes</a>
                    <a class="btn btn-muted" href="index.php?r=commercial.crm.import.template&type=compras">Baixar modelo compras</a>
                </div>
                <p class="helper-inline"><strong>Cadastro base (Clientes)</strong>: importa/atualiza cadastro do cliente no CRM. Use para trazer a base do ERP.</p>
                <p class="helper-inline"><strong>Historico de vendas (Compras)</strong>: importa compras para alimentar total gasto, ticket medio, ultima compra e status.</p>
                <p class="helper-inline"><strong>Chave recomendada:</strong> <code>codigo_cliente_erp</code> (PK do ERP). Se nao vier, usa <code>telefone</code> como vinculo.</p>
                <p class="helper-inline"><strong>CSV Clientes</strong> (cabecalho): <code>codigo_cliente_erp,nome,telefone,email,data_nascimento,observacoes</code></p>
                <p class="helper-inline"><strong>CSV Compras</strong> (cabecalho): <code>codigo_cliente_erp,telefone,data_compra,valor,forma_pagamento,numero_pedido,nota_fiscal,produtos,observacao</code></p>
                <p class="helper-inline muted">Aceita delimitador <code>,</code> ou <code>;</code>. Telefone preferencialmente so numeros. Data em <code>YYYY-MM-DD</code>.</p>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <div class="table-head">
        <div class="card-headline">
            <h2>Clientes</h2>
            <p>Operacao diaria: filtre rapido e abra o cadastro do cliente para compras/observacoes.</p>
        </div>
        <?php if (!empty($canManageSettings)): ?>
            <a class="btn" href="index.php?r=settings#config-cadastros">+ Novo Cliente</a>
        <?php else: ?>
            <button type="button" class="btn btn-muted" data-toggle-target="newClientBox">+ Novo Cliente</button>
        <?php endif; ?>
    </div>

    <?php if (empty($canManageSettings)): ?>
    <div id="newClientBox" class="quick-box hidden">
        <p class="muted helper-inline">Cadastro mestre de clientes fica em Configuracoes. Se voce nao tiver acesso, solicite importacao/cadastro ao administrador.</p>
        <form method="post" action="index.php?r=commercial.crm.client.store" class="form-grid three">
            <input type="text" name="client_name" placeholder="Nome" required>
            <input type="text" name="phone" placeholder="Telefone (unico)" required>
            <input type="email" name="email" placeholder="Email (opcional)">
            <input type="date" name="birth_date" placeholder="Data de nascimento">
            <?php if ($canSeeAll): ?>
                <select name="owner_user_id">
                    <option value="">Vendedor responsavel (padrao: voce)</option>
                    <?php foreach ($users as $userRow): ?>
                        <option value="<?= (int) $userRow['id'] ?>"><?= htmlspecialchars((string) $userRow['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <div class="full">
                <textarea name="notes" rows="2" placeholder="Observacoes (opcional)"></textarea>
            </div>
            <div class="actions-inline full">
                <button type="submit">Salvar cliente</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <form method="get" action="index.php" class="form-grid four compact-form">
        <input type="hidden" name="r" value="commercial.crm">
        <input type="hidden" name="main_search" value="<?= htmlspecialchars($mainSearch) ?>">
        <input type="hidden" name="client_code_filter" value="">
        <input type="hidden" name="client_name_filter" value="">
        <select name="status_filter">
            <option value="">Status (todos)</option>
            <option value="NOVO" <?= $statusCustomerFilter === 'NOVO' ? 'selected' : '' ?>>NOVO</option>
            <option value="ATIVO" <?= $statusCustomerFilter === 'ATIVO' ? 'selected' : '' ?>>ATIVO</option>
            <option value="VIP" <?= $statusCustomerFilter === 'VIP' ? 'selected' : '' ?>>VIP</option>
            <option value="INATIVO" <?= $statusCustomerFilter === 'INATIVO' ? 'selected' : '' ?>>INATIVO</option>
        </select>
        <input type="text" name="neighborhood_filter" value="<?= htmlspecialchars($neighborhoodFilter) ?>" placeholder="Bairro">
        <input type="text" name="min_total_spent" value="<?= htmlspecialchars($minTotalSpentFilter) ?>" placeholder="Faixa gasto (min)">
        <input type="text" name="max_total_spent" value="<?= htmlspecialchars($maxTotalSpentFilter) ?>" placeholder="Faixa gasto (max)">
        <?php if ($canSeeAll): ?>
            <select name="owner_user_id">
                <option value="">Vendedor (todos)</option>
                <?php foreach ($users as $userRow): ?>
                    <option value="<?= (int) $userRow['id'] ?>" <?= $ownerFilter === (int) $userRow['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $userRow['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <div class="actions-inline full">
            <button type="submit" class="btn btn-muted">Aplicar filtros</button>
            <?php if ($statusCustomerFilter !== '' || $neighborhoodFilter !== '' || $minTotalSpentFilter !== '' || $maxTotalSpentFilter !== '' || $ownerFilter > 0): ?>
                <a class="btn btn-muted" href="<?= htmlspecialchars($buildCrmUrl([
                    'status_filter' => null,
                    'neighborhood_filter' => null,
                    'min_total_spent' => null,
                    'max_total_spent' => null,
                    'owner_user_id' => null,
                ])) ?>">Limpar filtros</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>UltimaCompra</th>
                <th>TotalComprado</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $row): ?>
                <tr>
                    <td>
                        <a href="index.php?r=commercial.crm.client&id=<?= (int) $row['id'] ?>">
                            <?= htmlspecialchars((string) ($row['client_name'] ?? '')) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['phone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['last_purchase_date'] ?? '-')) ?></td>
                    <td>R$ <?= number_format((float) ($row['total_spent'] ?? 0), 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars((string) ($row['status_customer'] ?? '')) ?></td>
                    <td>
                        <div class="actions-inline">
                            <a class="btn btn-muted" href="index.php?r=commercial.crm.client&id=<?= (int) $row['id'] ?>&tab=compras">Ver</a>
                            <a class="btn btn-muted" href="index.php?r=commercial.crm.client&id=<?= (int) $row['id'] ?>&tab=observacoes">Editar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($clients)): ?>
                <tr><td colspan="6">Nenhum cliente encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalItems > 0): ?>
        <div class="pagination-bar">
            <p class="muted">
                <?= $totalItems ?> cliente(s) - pagina <?= $currentPage ?> de <?= $totalPages ?>
            </p>
            <div class="actions-inline">
                <?php if ($currentPage > 1): ?>
                    <a class="btn btn-muted" href="<?= htmlspecialchars($buildCrmUrl(['page' => $currentPage - 1])) ?>">Anterior</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <a class="btn <?= $p === $currentPage ? '' : 'btn-muted' ?>" href="<?= htmlspecialchars($buildCrmUrl(['page' => $p])) ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="btn btn-muted" href="<?= htmlspecialchars($buildCrmUrl(['page' => $currentPage + 1])) ?>">Proxima</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
