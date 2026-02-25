<section class="page-head">
    <h1>Configuracoes do Sistema</h1>
    <p>Modulo administrativo para cadastros base, usuarios, permissoes e acessos.</p>
</section>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Cadastro salvo com sucesso.</div>
<?php elseif ((string) $success === '2'): ?>
    <div class="alert success">Item atualizado com sucesso.</div>
<?php elseif ((string) $success === '3'): ?>
    <div class="alert success">Item removido com sucesso.</div>
<?php endif; ?>
<?php if ((string) $error === '1'): ?>
    <div class="alert error">Informe dados validos para continuar.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel salvar. Verifique duplicidade ou dependencia.</div>
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
