<section class="page-head">
    <h1>Ativos gerais de TI</h1>
    <p>Cadastro objetivo de inventario. Contratos e termos ficam em aba separada.</p>
</section>

<section class="card">
    <h2>
        <?php if ($editingAsset): ?>
            Editar ativo
        <?php elseif ($canStore): ?>
            Novo ativo
        <?php else: ?>
            Consulta de ativos
        <?php endif; ?>
    </h2>

    <?php if ((string) $success === '1'): ?>
        <div class="alert success">Ativo cadastrado com sucesso.</div>
    <?php elseif ((string) $success === '2'): ?>
        <div class="alert success">Ativo atualizado com sucesso.</div>
    <?php elseif ((string) $success === '3'): ?>
        <div class="alert success">Ativo removido com sucesso.</div>
    <?php elseif ((string) $success === '4'): ?>
        <div class="alert success">Transferencia registrada com sucesso.</div>
    <?php elseif ((string) $success === '5'): ?>
        <div class="alert success">Departamento cadastrado rapidamente.</div>
    <?php elseif ((string) $success === '6'): ?>
        <div class="alert success">Responsavel cadastrado rapidamente.</div>
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
    <?php elseif ((string) $error === '6'): ?>
        <div class="alert error">Preencha os dados do cadastro rapido.</div>
    <?php elseif ((string) $error === '7'): ?>
        <div class="alert error">Nao foi possivel salvar no cadastro rapido.</div>
    <?php endif; ?>

    <?php if ($canStore || $editingAsset): ?>
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
            <label>Nome do equipamento</label>
            <input type="text" name="asset_name" placeholder="Ex: Notebook corporativo" value="<?= htmlspecialchars((string) ($editingAsset['asset_name'] ?? '')) ?>" required>
        </div>

        <div>
            <label>Marca</label>
            <input type="text" name="brand_name" placeholder="Ex: Dell" value="<?= htmlspecialchars((string) ($editingAsset['brand_name'] ?? '')) ?>">
        </div>

        <div>
            <label>Modelo</label>
            <input type="text" name="model_name" placeholder="Ex: Latitude 5440" value="<?= htmlspecialchars((string) ($editingAsset['model_name'] ?? '')) ?>">
        </div>

        <div>
            <label>Serial</label>
            <input type="text" name="serial_number" placeholder="Numero serial" value="<?= htmlspecialchars((string) ($editingAsset['serial_number'] ?? '')) ?>">
        </div>

        <div>
            <label>Propriedade</label>
            <?php $ownershipValue = (string) ($editingAsset['ownership_type'] ?? 'proprio'); ?>
            <select name="ownership_type" required>
                <option value="proprio" <?= $ownershipValue === 'proprio' ? 'selected' : '' ?>>Equipamento proprio</option>
                <option value="terceirizado" <?= $ownershipValue === 'terceirizado' ? 'selected' : '' ?>>Terceirizado</option>
            </select>
        </div>

        <div>
            <div class="field-head">
                <label>Responsavel</label>
                <?php if ($canQuickStaff): ?>
                    <button type="button" class="quick-fab" data-toggle-target="quickStaffBox" title="Cadastro rapido de responsavel">+</button>
                <?php endif; ?>
            </div>
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
            <?php if ($canQuickStaff): ?>
                <div id="quickStaffBox" class="quick-box hidden">
                    <form method="post" action="index.php?r=ti.assets.quick-staff.store" class="form-grid two">
                        <input type="text" name="name" placeholder="Nome do responsavel" required>
                        <input type="email" name="email" placeholder="Email (opcional)">
                        <?php if ($departmentScope !== null && $departmentScope !== '__none__'): ?>
                            <input type="hidden" name="department_id" value="<?= (int) $scopeDepartmentId ?>">
                            <input type="text" value="<?= htmlspecialchars((string) $departmentScope) ?>" readonly>
                        <?php else: ?>
                            <select name="department_id" required>
                                <option value="">Departamento</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= (int) $department['id'] ?>"><?= htmlspecialchars((string) $department['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <div class="actions-inline full">
                            <button type="submit">Salvar rapido</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="field-head">
                <label>Departamento do ativo</label>
                <?php if ($canQuickDepartment): ?>
                    <button type="button" class="quick-fab" data-toggle-target="quickDepartmentBox" title="Cadastro rapido de departamento">+</button>
                <?php endif; ?>
            </div>
            <select name="department_id">
                <option value="">Nao vinculado a departamento</option>
                <?php foreach ($departments as $department): ?>
                    <option
                        value="<?= (int) $department['id'] ?>"
                        <?= $editingAsset && (int) ($editingAsset['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($canQuickDepartment): ?>
                <div id="quickDepartmentBox" class="quick-box hidden">
                    <form method="post" action="index.php?r=ti.assets.quick-department.store" class="inline-form">
                        <input type="text" name="name" placeholder="Nome do departamento" required>
                        <button type="submit">Salvar rapido</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <label>Rede</label>
            <?php $networkModeValue = (string) ($editingAsset['network_mode'] ?? ''); ?>
            <select name="network_mode">
                <option value="">Nao se aplica</option>
                <option value="dhcp" <?= $networkModeValue === 'dhcp' ? 'selected' : '' ?>>DHCP</option>
                <option value="estatico" <?= $networkModeValue === 'estatico' ? 'selected' : '' ?>>IP estatico</option>
            </select>
        </div>

        <div>
            <label>IP fixo (se estatico)</label>
            <input type="text" name="ip_address" placeholder="Ex: 192.168.1.50" value="<?= htmlspecialchars((string) ($editingAsset['ip_address'] ?? '')) ?>">
        </div>

        <details class="full subtle-details">
            <summary>Campos complementares</summary>
            <div class="form-grid two details-grid">
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
                <div class="full">
                    <label>Observacao</label>
                    <textarea name="observation" rows="3" placeholder="Anotacoes internas"><?= htmlspecialchars((string) ($editingAsset['observation'] ?? $editingAsset['notes'] ?? '')) ?></textarea>
                </div>
            </div>
        </details>

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
    <?php else: ?>
        <p class="muted">Seu perfil esta em modo consulta. Cadastros e alteracoes ficam com gestor/administrador.</p>
    <?php endif; ?>
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
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por TAG, nome, marca, modelo, serial...">
            <select name="department_id">
                <option value="">Todos departamentos</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int) $department['id'] ?>" <?= (int) ($selectedDepartmentId ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="responsible_id">
                <option value="">Todos responsaveis</option>
                <?php foreach ($staff as $person): ?>
                    <option value="<?= (int) $person['id'] ?>" <?= (int) ($selectedResponsibleId ?? 0) === (int) $person['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $person['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Buscar</button>
            <a class="btn btn-muted" href="index.php?r=ti.assets">Limpar</a>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Equipamento</th>
                    <th>TAG</th>
                    <th>Status</th>
                    <th>Departamento</th>
                    <th>Responsavel</th>
                    <th>Detalhes</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) ($asset['asset_name'] ?? '')) ?>
                            <?php if (!empty($asset['brand_name']) || !empty($asset['model_name'])): ?>
                                <br><span class="muted small"><?= htmlspecialchars(trim((string) (($asset['brand_name'] ?? '') . ' ' . ($asset['model_name'] ?? '')))) ?></span>
                            <?php endif; ?>
                            <br><span class="muted small"><?= htmlspecialchars((string) ($asset['category_name'] ?? $asset['type'])) ?></span>
                        </td>
                        <td><?= htmlspecialchars((string) $asset['tag']) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['status_name'] ?? $asset['status'])) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['asset_department_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['staff_name'] ?? '')) ?></td>
                        <td class="muted small">
                            <?= htmlspecialchars((string) ($asset['ownership_type'] ?? '')) ?>
                            <?php if (!empty($asset['condition_state'])): ?>
                                <br>Estado: <?= htmlspecialchars((string) $asset['condition_state']) ?>
                            <?php endif; ?>
                            <?php if (!empty($asset['network_mode']) || !empty($asset['ip_address'])): ?>
                                <br>Rede: <?= htmlspecialchars((string) ($asset['network_mode'] ?? '')) ?><?= !empty($asset['ip_address']) ? ' / ' . htmlspecialchars((string) $asset['ip_address']) : '' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-inline">
                                <?php if ($canUpdate): ?>
                                    <a class="btn btn-muted" href="index.php?r=ti.assets&edit=<?= (int) $asset['id'] ?>">Editar</a>
                                <?php endif; ?>
                                <?php if ($canTransfer): ?>
                                    <a class="btn btn-transfer" href="index.php?r=ti.assets&transfer=<?= (int) $asset['id'] ?>">Transferir</a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post" action="index.php?r=ti.assets.delete" onsubmit="return confirm('Deseja excluir este ativo?');">
                                        <input type="hidden" name="id" value="<?= (int) $asset['id'] ?>">
                                        <button class="btn-danger" type="submit">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="7">Nenhum ativo cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
