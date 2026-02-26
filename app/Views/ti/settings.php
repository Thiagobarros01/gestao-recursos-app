<section class="page-head">
    <h1>Configuracoes do Sistema</h1>
    <p>Modulo administrativo para cadastros base, usuarios, permissoes e acessos.</p>
</section>

<?php
$crmSettings = $crmSettings ?? [];
$canManageCrmSettings = !empty($canManageCrmSettings);
$crmMasterClients = $crmMasterClients ?? [];
$productsCatalog = $productsCatalog ?? [];
$editingCrmClient = $editingCrmClient ?? null;
$editingProduct = $editingProduct ?? null;
$editingStaffMember = $editingStaffMember ?? null;
?>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Cadastro salvo com sucesso.</div>
<?php elseif ((string) $success === '2'): ?>
    <div class="alert success">Item atualizado com sucesso.</div>
<?php elseif ((string) $success === '3'): ?>
    <div class="alert success">Item removido com sucesso.</div>
<?php elseif ((string) $success === '4'): ?>
    <div class="alert success">Configuracao do CRM atualizada com sucesso.</div>
<?php endif; ?>
<?php if ((string) $error === '1'): ?>
    <div class="alert error">Informe dados validos para continuar.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel salvar. Verifique duplicidade ou dependencia.</div>
<?php endif; ?>

<?php if ($canManageCrmSettings): ?>
<section id="crm-config" class="card">
    <div class="table-head">
        <div class="card-headline">
            <h2>Administracao - Configuracao CRM</h2>
            <p>Regras de classificacao automatica do CRM Lite B2C (visivel apenas para admin).</p>
        </div>
    </div>
    <form method="post" action="index.php?r=settings.crm.update" class="form-grid four">
        <label class="permission-item full">
            <input type="checkbox" name="auto_status_enabled" value="1" <?= (int) ($crmSettings['auto_status_enabled'] ?? 1) === 1 ? 'checked' : '' ?>>
            <span>Permitir calculo automatico de status (Ativo/Inativo/VIP/Novo)</span>
        </label>
        <div>
            <label for="crm_followup_after_days">Reativacao apos (dias)</label>
            <input id="crm_followup_after_days" type="number" min="1" max="3650" name="followup_after_days" value="<?= (int) ($crmSettings['followup_after_days'] ?? 30) ?>" required>
        </div>
        <div>
            <label for="crm_vip_amount_threshold">Limite VIP (R$)</label>
            <input id="crm_vip_amount_threshold" type="text" name="vip_amount_threshold" value="<?= htmlspecialchars((string) ($crmSettings['vip_amount_threshold'] ?? '1000')) ?>" required>
        </div>
        <div>
            <label for="crm_inactive_after_days">Inativo apos (dias)</label>
            <input id="crm_inactive_after_days" type="number" min="1" max="3650" name="inactive_after_days" value="<?= (int) ($crmSettings['inactive_after_days'] ?? 60) ?>" required>
        </div>
        <div>
            <label for="crm_new_after_days">Novo ate (dias)</label>
            <input id="crm_new_after_days" type="number" min="1" max="3650" name="new_after_days" value="<?= (int) ($crmSettings['new_after_days'] ?? 30) ?>" required>
        </div>
        <input type="hidden" name="active_after_days" value="<?= (int) ($crmSettings['inactive_after_days'] ?? 60) ?>">
        <input type="hidden" name="recurrence_window_days" value="<?= (int) ($crmSettings['recurrence_window_days'] ?? 90) ?>">
        <input type="hidden" name="recurrence_min_purchases" value="<?= (int) ($crmSettings['recurrence_min_purchases'] ?? 3) ?>">
        <div class="actions-inline">
            <button type="submit">Salvar configuracao CRM</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="card-grid triple">
    <article class="card">
        <h2>Categorias de equipamento</h2>
        <form method="post" action="index.php?r=settings.categories.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Impressora" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingCategory): ?>
            <form method="post" action="index.php?r=settings.categories.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingCategory['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingCategory['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($categories as $category): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars($category['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=settings&category_edit=<?= (int) $category['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=settings.categories.delete" onsubmit="return confirm('Deseja excluir esta categoria?');">
                            <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                            <button class="btn-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Tipos de contrato</h2>
        <form method="post" action="index.php?r=settings.contract-types.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Estagio" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingContractType): ?>
            <form method="post" action="index.php?r=settings.contract-types.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingContractType['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingContractType['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($contractTypes as $contractType): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars($contractType['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=settings&contract_edit=<?= (int) $contractType['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=settings.contract-types.delete" onsubmit="return confirm('Deseja excluir este tipo de contrato?');">
                            <input type="hidden" name="id" value="<?= (int) $contractType['id'] ?>">
                            <button class="btn-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Status do ativo</h2>
        <form method="post" action="index.php?r=settings.statuses.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Em manutencao" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingStatus): ?>
            <form method="post" action="index.php?r=settings.statuses.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingStatus['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingStatus['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($statuses as $status): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars($status['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=settings&status_edit=<?= (int) $status['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=settings.statuses.delete" onsubmit="return confirm('Deseja excluir este status?');">
                            <input type="hidden" name="id" value="<?= (int) $status['id'] ?>">
                            <button class="btn-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<section class="card-grid">
    <article class="card">
        <h2>Departamentos</h2>
        <form method="post" action="index.php?r=settings.departments.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Financeiro" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingDepartment): ?>
            <form method="post" action="index.php?r=settings.departments.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingDepartment['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingDepartment['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($departments as $department): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars((string) $department['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=settings&department_edit=<?= (int) $department['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=settings.departments.delete" onsubmit="return confirm('Deseja excluir este departamento?');">
                            <input type="hidden" name="id" value="<?= (int) $department['id'] ?>">
                            <button class="btn-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Cadastro de colaboradores</h2>
        <form method="post" action="index.php?r=<?= $editingStaffMember ? 'settings.staff.update' : 'settings.staff.store' ?>" class="form-grid two">
            <?php if ($editingStaffMember): ?>
                <input type="hidden" name="id" value="<?= (int) $editingStaffMember['id'] ?>">
            <?php endif; ?>
            <input type="text" name="name" placeholder="Nome do colaborador" value="<?= htmlspecialchars((string) ($editingStaffMember['name'] ?? '')) ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars((string) ($editingStaffMember['email'] ?? '')) ?>">
            <select name="department" required>
                <option value="">Departamento</option>
                <?php foreach ($departments as $department): ?>
                    <?php $deptName = (string) ($department['name'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($deptName) ?>" <?= (string) ($editingStaffMember['department'] ?? '') === $deptName ? 'selected' : '' ?>>
                        <?= htmlspecialchars($deptName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="actions-inline full">
                <button type="submit"><?= $editingStaffMember ? 'Atualizar colaborador' : 'Criar colaborador' ?></button>
                <?php if ($editingStaffMember): ?>
                    <a class="btn btn-muted" href="index.php?r=settings">Cancelar edicao</a>
                <?php endif; ?>
            </div>
        </form>
        <ul class="simple-list stack-list">
            <?php foreach (array_slice($staffMembers, 0, 25) as $staffMember): ?>
                <li class="stack-item">
                    <span>
                        <?= htmlspecialchars((string) $staffMember['name']) ?>
                        <?= !empty($staffMember['department']) ? ' - ' . htmlspecialchars((string) $staffMember['department']) : '' ?>
                    </span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=settings&staff_edit=<?= (int) $staffMember['id'] ?>#config-cadastros">Editar</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Usuarios e acessos</h2>
        <form method="post" action="index.php?r=<?= $editingUser ? 'settings.users.update' : 'settings.users.store' ?>" class="form-grid two">
            <?php if ($editingUser): ?>
                <input type="hidden" name="id" value="<?= (int) $editingUser['id'] ?>">
            <?php endif; ?>

            <input type="text" name="name" placeholder="Nome completo" value="<?= htmlspecialchars((string) ($editingUser['name'] ?? '')) ?>" required>
            <input type="text" name="username" placeholder="Login" value="<?= htmlspecialchars((string) ($editingUser['username'] ?? '')) ?>" required>
            <input type="password" name="password" placeholder="<?= $editingUser ? 'Nova senha (opcional)' : 'Senha inicial' ?>" <?= $editingUser ? '' : 'required' ?>>

            <select name="role" required>
                <?php $roleValue = (string) ($editingUser['role'] ?? 'operador'); ?>
                <option value="admin" <?= $roleValue === 'admin' ? 'selected' : '' ?>>Administrador</option>
                <option value="ti" <?= $roleValue === 'ti' ? 'selected' : '' ?>>TI (total)</option>
                <option value="gestor" <?= $roleValue === 'gestor' ? 'selected' : '' ?>>Gestor</option>
                <option value="operador" <?= $roleValue === 'operador' ? 'selected' : '' ?>>Operador</option>
            </select>

            <select name="department_id">
                <option value="">Sem departamento</option>
                <?php foreach ($departments as $department): ?>
                    <option
                        value="<?= (int) $department['id'] ?>"
                        <?= (int) ($editingUser['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="staff_id">
                <option value="">Sem colaborador vinculado</option>
                <?php foreach ($staffMembers as $staffMember): ?>
                    <option
                        value="<?= (int) $staffMember['id'] ?>"
                        <?= (int) ($editingUser['staff_id'] ?? 0) === (int) $staffMember['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $staffMember['name']) ?><?= !empty($staffMember['department']) ? ' - ' . htmlspecialchars((string) $staffMember['department']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="permission-item">
                <input type="checkbox" name="is_seller" value="1" <?= (int) ($editingUser['is_seller'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span>Operador vendedor (acesso ao modulo vendedor/compras)</span>
            </label>

            <div class="full permission-grid">
                <p class="muted">Permissoes extras selecionadas pelo administrador:</p>
                <?php
                    $currentAllowed = [];
                    if ($editingUser && isset($editingUser['allowed_routes']) && is_array($editingUser['allowed_routes'])) {
                        $currentAllowed = $editingUser['allowed_routes'];
                    }
                ?>
                <?php foreach ($permissionGroups as $key => $group): ?>
                    <?php
                        $checked = false;
                        foreach ($group['routes'] as $route) {
                            if (in_array($route, $currentAllowed, true)) {
                                $checked = true;
                                break;
                            }
                        }
                    ?>
                    <label class="permission-item">
                        <input type="checkbox" name="permission_groups[]" value="<?= htmlspecialchars((string) $key) ?>" <?= $checked ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars((string) $group['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="actions-inline full">
                <button type="submit"><?= $editingUser ? 'Atualizar usuario' : 'Criar usuario' ?></button>
                <?php if ($editingUser): ?>
                    <a class="btn btn-muted" href="index.php?r=settings">Cancelar edicao</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Papel</th>
                        <th>Vendedor</th>
                        <th>Departamento</th>
                        <th>Colaborador</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $row['name']) ?></td>
                            <td><?= htmlspecialchars((string) $row['username']) ?></td>
                            <td><?= htmlspecialchars((string) $row['role']) ?></td>
                            <td><?= (int) ($row['is_seller'] ?? 0) === 1 ? 'Sim' : 'Nao' ?></td>
                            <td><?= htmlspecialchars((string) ($row['department_name'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($row['staff_name'] ?? '')) ?></td>
                            <td>
                                <a class="btn btn-muted" href="index.php?r=settings&user_edit=<?= (int) $row['id'] ?>">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7">Nenhum usuario cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section id="config-cadastros" class="card-grid">
    <article class="card">
        <h2>Cadastro mestre de clientes (CRM)</h2>
        <form method="post" action="index.php?r=<?= $editingCrmClient ? 'settings.crm.clients.update' : 'settings.crm.clients.store' ?>" class="form-grid two">
            <?php if ($editingCrmClient): ?>
                <input type="hidden" name="id" value="<?= (int) $editingCrmClient['id'] ?>">
            <?php endif; ?>
            <input type="text" name="erp_customer_code" placeholder="Codigo ERP Cliente (obrigatorio)" value="<?= htmlspecialchars((string) ($editingCrmClient['erp_customer_code'] ?? '')) ?>" required>
            <input type="text" name="client_name" placeholder="Nome" value="<?= htmlspecialchars((string) ($editingCrmClient['client_name'] ?? '')) ?>" required>
            <input type="text" name="phone" placeholder="Telefone" value="<?= htmlspecialchars((string) ($editingCrmClient['phone'] ?? '')) ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars((string) ($editingCrmClient['email'] ?? '')) ?>">
            <input type="date" name="birth_date" value="<?= htmlspecialchars((string) ($editingCrmClient['birth_date'] ?? '')) ?>">
            <select name="owner_user_id" required>
                <option value="">Responsavel</option>
                <?php foreach ($users as $userRow): ?>
                    <option value="<?= (int) $userRow['id'] ?>" <?= (int) ($editingCrmClient['owner_user_id'] ?? 0) === (int) $userRow['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $userRow['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="full">
                <textarea name="notes" rows="2" placeholder="Observacoes"><?= htmlspecialchars((string) ($editingCrmClient['notes'] ?? '')) ?></textarea>
            </div>
            <div class="actions-inline full">
                <button type="submit"><?= $editingCrmClient ? 'Atualizar cliente' : 'Criar cliente' ?></button>
                <?php if ($editingCrmClient): ?>
                    <a class="btn btn-muted" href="index.php?r=settings#config-cadastros">Cancelar edicao</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Cod ERP</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Ultima compra</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($crmMasterClients, 0, 80) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['erp_customer_code'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($row['client_name'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($row['phone'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($row['last_purchase_date'] ?? '-')) ?></td>
                            <td class="actions-inline">
                                <a class="btn btn-muted" href="index.php?r=settings&crm_client_edit=<?= (int) $row['id'] ?>#config-cadastros">Editar</a>
                                <a class="btn btn-muted" href="index.php?r=commercial.crm.client&id=<?= (int) $row['id'] ?>">Ver CRM</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($crmMasterClients)): ?>
                        <tr><td colspan="5">Nenhum cliente cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <h2>Cadastro mestre de produtos</h2>
        <form method="post" action="index.php?r=<?= $editingProduct ? 'settings.products.update' : 'settings.products.store' ?>" class="form-grid two">
            <?php if ($editingProduct): ?>
                <input type="hidden" name="id" value="<?= (int) $editingProduct['id'] ?>">
            <?php endif; ?>
            <input type="text" name="erp_product_code" placeholder="Codigo ERP Produto (obrigatorio)" value="<?= htmlspecialchars((string) ($editingProduct['sku'] ?? '')) ?>" required>
            <input type="text" name="name" placeholder="Nome do produto" value="<?= htmlspecialchars((string) ($editingProduct['name'] ?? '')) ?>" required>
            <input type="number" name="stock_qty" min="0" placeholder="Estoque" value="<?= (int) ($editingProduct['stock_qty'] ?? 0) ?>">
            <input type="number" name="min_qty" min="0" placeholder="Estoque minimo" value="<?= (int) ($editingProduct['min_qty'] ?? 0) ?>">
            <div class="actions-inline full">
                <button type="submit"><?= $editingProduct ? 'Atualizar produto' : 'Criar produto' ?></button>
                <?php if ($editingProduct): ?>
                    <a class="btn btn-muted" href="index.php?r=settings#config-cadastros">Cancelar edicao</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Cod ERP</th>
                        <th>Produto</th>
                        <th>Estoque</th>
                        <th>Min</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($productsCatalog, 0, 100) as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($product['sku'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($product['name'] ?? '')) ?></td>
                            <td><?= (int) ($product['stock_qty'] ?? 0) ?></td>
                            <td><?= (int) ($product['min_qty'] ?? 0) ?></td>
                            <td class="actions-inline">
                                <a class="btn btn-muted" href="index.php?r=settings&product_edit=<?= (int) $product['id'] ?>#config-cadastros">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($productsCatalog)): ?>
                        <tr><td colspan="5">Nenhum produto cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
