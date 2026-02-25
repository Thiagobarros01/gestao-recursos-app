<section class="page-head">
    <h1>Ativos e contratos TI</h1>
    <p>Controle de equipamento com datas e historico de movimentacoes.</p>
</section>

<section class="card">
    <h2><?= $editingAsset ? 'Editar ativo' : 'Novo ativo' ?></h2>

    <?php if ((string) $success === '1'): ?>
        <div class="alert success">Ativo cadastrado com sucesso.</div>
    <?php elseif ((string) $success === '2'): ?>
        <div class="alert success">Ativo atualizado com sucesso.</div>
    <?php elseif ((string) $success === '3'): ?>
        <div class="alert success">Ativo removido com sucesso.</div>
    <?php elseif ((string) $success === '4'): ?>
        <div class="alert success">Transferencia registrada com sucesso.</div>
    <?php endif; ?>
    <?php if ((string) $error === '1'): ?>
        <div class="alert error">Preencha os campos obrigatorios.</div>
    <?php elseif ((string) $error === '2'): ?>
        <div class="alert error">Erro ao salvar. Verifique TAG duplicada ou dados invalidos.</div>
    <?php elseif ((string) $error === '3'): ?>
        <div class="alert error">Ativo nao encontrado para edicao/exclusao.</div>
    <?php elseif ((string) $error === '4'): ?>
        <div class="alert error">Dados invalidos para transferencia.</div>
    <?php elseif ((string) $error === '5'): ?>
        <div class="alert error">Nao foi possivel concluir a transferencia.</div>
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
            <label>Estado do equipamento</label>
            <select name="condition_state">
                <?php $conditionValue = (string) ($editingAsset['condition_state'] ?? ''); ?>
                <option value="">Nao informado</option>
                <option value="Novo" <?= $conditionValue === 'Novo' ? 'selected' : '' ?>>Novo</option>
                <option value="Bom" <?= $conditionValue === 'Bom' ? 'selected' : '' ?>>Bom</option>
                <option value="Regular" <?= $conditionValue === 'Regular' ? 'selected' : '' ?>>Regular</option>
                <option value="Avariado" <?= $conditionValue === 'Avariado' ? 'selected' : '' ?>>Avariado</option>
                <option value="Em manutencao" <?= $conditionValue === 'Em manutencao' ? 'selected' : '' ?>>Em manutencao</option>
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

        <div>
            <label>Data da compra</label>
            <input type="date" name="purchase_date" value="<?= htmlspecialchars((string) ($editingAsset['purchase_date'] ?? '')) ?>">
        </div>

        <div>
            <label>Garantia ate</label>
            <input type="date" name="warranty_until" value="<?= htmlspecialchars((string) ($editingAsset['warranty_until'] ?? '')) ?>">
        </div>

        <div>
            <label>Contrato ate</label>
            <input type="date" name="contract_until" value="<?= htmlspecialchars((string) ($editingAsset['contract_until'] ?? '')) ?>">
        </div>

        <div>
            <label>Data de devolucao</label>
            <input type="date" name="returned_at" value="<?= htmlspecialchars((string) ($editingAsset['returned_at'] ?? '')) ?>">
        </div>

        <div class="full">
            <label>Caminho do documento na nuvem</label>
            <input
                type="text"
                name="document_path"
                placeholder="Ex: https://drive... ou /pasta/documento.pdf"
                value="<?= htmlspecialchars((string) ($editingAsset['document_path'] ?? '')) ?>"
            >
        </div>

        <div class="full">
            <label>Observacao</label>
            <textarea name="observation" rows="3" placeholder="Anotacoes relevantes do ativo e contrato"><?= htmlspecialchars((string) ($editingAsset['observation'] ?? $editingAsset['notes'] ?? '')) ?></textarea>
        </div>

        <?php if ($editingAsset): ?>
            <div class="full">
                <label>Motivo da alteracao (aparece no historico)</label>
                <input type="text" name="movement_note" placeholder="Ex: Troca de colaborador apos desligamento">
            </div>
        <?php endif; ?>

        <div class="actions-inline full">
            <button type="submit"><?= $editingAsset ? 'Atualizar ativo' : 'Salvar ativo' ?></button>
            <?php if ($editingAsset): ?>
                <a class="btn btn-muted" href="index.php?r=ti.assets">Cancelar edicao</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if ($editingAsset): ?>
    <section class="card">
        <h2>Historico do ativo #<?= (int) $editingAsset['id'] ?></h2>
        <?php if (empty($movements)): ?>
            <p class="muted">Sem movimentacoes registradas ainda.</p>
        <?php else: ?>
            <ul class="timeline-list">
                <?php foreach ($movements as $movement): ?>
                    <li class="timeline-item">
                        <p class="timeline-head">
                            <strong><?= htmlspecialchars((string) $movement['movement_type']) ?></strong>
                            <span><?= htmlspecialchars((string) $movement['created_at']) ?></span>
                        </p>
                        <?php if (!empty($movement['details'])): ?>
                            <p class="timeline-body"><?= htmlspecialchars((string) $movement['details']) ?></p>
                        <?php endif; ?>
                        <p class="timeline-meta">
                            <?php if (!empty($movement['from_status']) || !empty($movement['to_status'])): ?>
                                <span>Status: <?= htmlspecialchars((string) $movement['from_status']) ?> -> <?= htmlspecialchars((string) $movement['to_status']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($movement['from_staff']) || !empty($movement['to_staff'])): ?>
                                <span>Responsavel: <?= htmlspecialchars((string) $movement['from_staff']) ?> -> <?= htmlspecialchars((string) $movement['to_staff']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($movement['changed_by'])): ?>
                                <span>Por: <?= htmlspecialchars((string) $movement['changed_by']) ?></span>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($transferringAsset): ?>
    <section class="card">
        <h2>Transferir ativo #<?= (int) $transferringAsset['id'] ?> - <?= htmlspecialchars((string) $transferringAsset['tag']) ?></h2>
        <p class="muted">
            Responsavel atual:
            <strong><?= htmlspecialchars((string) ($transferringAsset['staff_name'] ?: 'Nao vinculado')) ?></strong>
        </p>

        <form method="post" action="index.php?r=ti.assets.transfer" class="form-grid three">
            <input type="hidden" name="id" value="<?= (int) $transferringAsset['id'] ?>">

            <div>
                <label>Novo responsavel</label>
                <select name="to_staff_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($staff as $person): ?>
                        <option value="<?= (int) $person['id'] ?>"><?= htmlspecialchars((string) $person['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Novo status (opcional)</label>
                <select name="status_id">
                    <option value="">Manter atual</option>
                    <?php foreach ($statuses as $status): ?>
                        <option
                            value="<?= (int) $status['id'] ?>"
                            <?= (int) ($transferringAsset['status_id'] ?? 0) === (int) $status['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars((string) $status['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full">
                <label>Motivo da transferencia</label>
                <textarea name="reason" rows="3" placeholder="Ex: desligamento do colaborador anterior, realocacao de equipe" required></textarea>
            </div>

            <div class="actions-inline full">
                <button type="submit">Confirmar transferencia</button>
                <a class="btn btn-muted" href="index.php?r=ti.assets">Cancelar</a>
            </div>
        </form>

        <?php if (!empty($transferMovements)): ?>
            <h3>Ultimas movimentacoes</h3>
            <ul class="timeline-list">
                <?php foreach ($transferMovements as $movement): ?>
                    <li class="timeline-item">
                        <p class="timeline-head">
                            <strong><?= htmlspecialchars((string) $movement['movement_type']) ?></strong>
                            <span><?= htmlspecialchars((string) $movement['created_at']) ?></span>
                        </p>
                        <?php if (!empty($movement['details'])): ?>
                            <p class="timeline-body"><?= htmlspecialchars((string) $movement['details']) ?></p>
                        <?php endif; ?>
                        <p class="timeline-meta">
                            <?php if (!empty($movement['from_staff']) || !empty($movement['to_staff'])): ?>
                                <span>Responsavel: <?= htmlspecialchars((string) $movement['from_staff']) ?> -> <?= htmlspecialchars((string) $movement['to_staff']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($movement['changed_by'])): ?>
                                <span>Por: <?= htmlspecialchars((string) $movement['changed_by']) ?></span>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

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
                    <th>Estado</th>
                    <th>Responsavel</th>
                    <th>Garantia</th>
                    <th>Contrato fim</th>
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
                        <td><?= htmlspecialchars((string) ($asset['condition_state'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['staff_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['warranty_until'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['contract_until'] ?? '')) ?></td>
                        <td>
                            <div class="actions-inline">
                                <a class="btn btn-muted" href="index.php?r=ti.assets&edit=<?= (int) $asset['id'] ?>">Editar</a>
                                <a class="btn btn-transfer" href="index.php?r=ti.assets&transfer=<?= (int) $asset['id'] ?>">Transferir</a>
                                <form method="post" action="index.php?r=ti.assets.delete" onsubmit="return confirm('Deseja excluir este ativo?');">
                                    <input type="hidden" name="id" value="<?= (int) $asset['id'] ?>">
                                    <button class="btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="9">Nenhum ativo cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
