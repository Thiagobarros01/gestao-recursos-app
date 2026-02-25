<section class="page-head">
    <h1>Ativos e contratos TI</h1>
    <p>Controle completo de equipamento, contrato, status, documento e observacoes.</p>
</section>

<section class="card">
    <h2>Novo ativo</h2>

    <?php if (!empty($success)): ?>
        <div class="alert success">Ativo cadastrado com sucesso.</div>
    <?php endif; ?>
    <?php if ((string) $error === '1'): ?>
        <div class="alert error">Preencha os campos obrigatorios.</div>
    <?php elseif ((string) $error === '2'): ?>
        <div class="alert error">Erro ao salvar. Verifique TAG duplicada ou dados invalidos.</div>
    <?php endif; ?>

    <form method="post" action="index.php?r=ti.assets.store" class="form-grid three">
        <div>
            <label>Categoria de equipamento</label>
            <select name="category_id" required>
                <option value="">Selecione</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Tipo de contrato</label>
            <select name="contract_type_id" required>
                <option value="">Selecione</option>
                <?php foreach ($contractTypes as $contractType): ?>
                    <option value="<?= (int) $contractType['id'] ?>"><?= htmlspecialchars($contractType['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Status</label>
            <select name="status_id" required>
                <option value="">Selecione</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= (int) $status['id'] ?>"><?= htmlspecialchars($status['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>TAG / Patrimonio</label>
            <input type="text" name="tag" placeholder="Ex: NTB-2026-001" required>
        </div>

        <div>
            <label>Serial</label>
            <input type="text" name="serial_number" placeholder="Numero serial">
        </div>

        <div>
            <label>Responsavel</label>
            <select name="staff_id">
                <option value="">Nao vinculado</option>
                <?php foreach ($staff as $person): ?>
                    <option value="<?= (int) $person['id'] ?>"><?= htmlspecialchars($person['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="full">
            <label>Caminho do documento assinado</label>
            <input type="text" name="document_path" placeholder="Ex: /docs/ti/contrato-usuario.pdf">
        </div>

        <div class="full">
            <label>Observacao</label>
            <textarea name="observation" rows="3" placeholder="Anotacoes relevantes do ativo e contrato"></textarea>
        </div>

        <button type="submit">Salvar ativo</button>
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
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="7">Nenhum ativo cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
