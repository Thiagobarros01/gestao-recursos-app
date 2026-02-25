<section class="page-head">
    <h1>Ativos e contratos TI</h1>
    <p>Controle completo de equipamento, contrato, status, documento e observacoes.</p>
</section>

<section class="card">
    <h2><?= $editingAsset ? 'Editar ativo' : 'Novo ativo' ?></h2>

    <?php if ((string) $success === '1'): ?>
        <div class="alert success">Ativo cadastrado com sucesso.</div>
    <?php elseif ((string) $success === '2'): ?>
        <div class="alert success">Ativo atualizado com sucesso.</div>
    <?php elseif ((string) $success === '3'): ?>
        <div class="alert success">Ativo removido com sucesso.</div>
    <?php endif; ?>
    <?php if ((string) $error === '1'): ?>
        <div class="alert error">Preencha os campos obrigatorios.</div>
    <?php elseif ((string) $error === '2'): ?>
        <div class="alert error">Erro ao salvar. Verifique TAG duplicada ou dados invalidos.</div>
    <?php elseif ((string) $error === '3'): ?>
        <div class="alert error">Ativo nao encontrado para edicao/exclusao.</div>
    <?php endif; ?>

    <form method="post" action="index.php?r=<?= $editingAsset ? 'ti.assets.update' : 'ti.assets.store' ?>" class="form-grid three">
        <?php if ($editingAsset): ?>
            <input type="hidden" name="id" value="<?= (int) $editingAsset['id'] ?>">
        <?php endif; ?>

        <div>
            <label>Categoria de equipamento</label>
            <select name="category_id" required>
                <option value="">Selecione</option>
                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int) $category['id'] ?>"
                        <?= $editingAsset && (int) $editingAsset['category_id'] === (int) $category['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Tipo de contrato</label>
            <select name="contract_type_id" required>
                <option value="">Selecione</option>
                <?php foreach ($contractTypes as $contractType): ?>
                    <option
                        value="<?= (int) $contractType['id'] ?>"
                        <?= $editingAsset && (int) $editingAsset['contract_type_id'] === (int) $contractType['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($contractType['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Status</label>
            <select name="status_id" required>
                <option value="">Selecione</option>
                <?php foreach ($statuses as $status): ?>
                    <option
                        value="<?= (int) $status['id'] ?>"
                        <?= $editingAsset && (int) $editingAsset['status_id'] === (int) $status['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($status['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>TAG / Patrimonio</label>
            <input type="text" name="tag" placeholder="Ex: NTB-2026-001" value="<?= htmlspecialchars((string) ($editingAsset['tag'] ?? '')) ?>" required>
        </div>

        <div>
            <label>Serial</label>
            <input type="text" name="serial_number" placeholder="Numero serial" value="<?= htmlspecialchars((string) ($editingAsset['serial_number'] ?? '')) ?>">
        </div>

        <div>
            <label>Responsavel</label>
            <select name="staff_id">
                <option value="">Nao vinculado</option>
                <?php foreach ($staff as $person): ?>
                    <option
                        value="<?= (int) $person['id'] ?>"
                        <?= $editingAsset && (int) ($editingAsset['staff_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($person['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="full">
            <label>Caminho do documento assinado</label>
            <input
                type="text"
                name="document_path"
                placeholder="Ex: /docs/ti/contrato-usuario.pdf"
                value="<?= htmlspecialchars((string) ($editingAsset['document_path'] ?? '')) ?>"
            >
        </div>

        <div class="full">
            <label>Observacao</label>
            <textarea name="observation" rows="3" placeholder="Anotacoes relevantes do ativo e contrato"><?= htmlspecialchars((string) ($editingAsset['observation'] ?? $editingAsset['notes'] ?? '')) ?></textarea>
        </div>

        <div class="actions-inline full">
            <button type="submit"><?= $editingAsset ? 'Atualizar ativo' : 'Salvar ativo' ?></button>
            <?php if ($editingAsset): ?>
                <a class="btn btn-muted" href="index.php?r=ti.assets">Cancelar edicao</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <div class="table-head">
        <h2>Ativos cadastrados</h2>
        <form method="get" action="index.php" class="search-form">
            <input type="hidden" name="r" value="ti.assets">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por TAG, serial, categoria, status, contrato...">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>TAG</th>
                    <th>Contrato</th>
                    <th>Status</th>
                    <th>Responsavel</th>
                    <th>Documento</th>
                    <th>Observacao</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($asset['category_name'] ?? $asset['type'])) ?></td>
                        <td><?= htmlspecialchars((string) $asset['tag']) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['contract_type_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['status_name'] ?? $asset['status'])) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['staff_name'] ?? '')) ?></td>
                        <td class="break-line"><?= htmlspecialchars((string) $asset['document_path']) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['observation'] ?? $asset['notes'] ?? '')) ?></td>
                        <td>
                            <div class="actions-inline">
                                <a class="btn btn-muted" href="index.php?r=ti.assets&edit=<?= (int) $asset['id'] ?>">Editar</a>
                                <form method="post" action="index.php?r=ti.assets.delete" onsubmit="return confirm('Deseja excluir este ativo?');">
                                    <input type="hidden" name="id" value="<?= (int) $asset['id'] ?>">
                                    <button class="btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="8">Nenhum ativo cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
