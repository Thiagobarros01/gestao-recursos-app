<section class="page-head">
    <h1>Contratos e termos</h1>
    <p>Controle de vigencias, documentos em nuvem e termos de retirada.</p>
</section>

<?php if ((string) ($success ?? '') === '1'): ?>
    <div class="alert success">Dados de contrato atualizados com sucesso.</div>
<?php endif; ?>
<?php if ((string) ($error ?? '') === '1'): ?>
    <div class="alert error">Dados invalidos para atualizar contrato.</div>
<?php elseif ((string) ($error ?? '') === '2'): ?>
    <div class="alert error">Nao foi possivel salvar os dados de contrato.</div>
<?php elseif ((string) ($error ?? '') === '3'): ?>
    <div class="alert error">Seu perfil nao pode alterar contratos.</div>
<?php endif; ?>

<?php if ($canManageContracts): ?>
    <section class="card">
        <h2>Atualizar contrato do ativo</h2>
        <form method="post" action="index.php?r=ti.contracts.update" class="form-grid three">
            <div>
                <label>Ativo</label>
                <select name="id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($assets as $asset): ?>
                        <option value="<?= (int) $asset['id'] ?>">
                            <?= htmlspecialchars((string) $asset['tag']) ?> - <?= htmlspecialchars((string) ($asset['asset_name'] ?? ($asset['category_name'] ?? $asset['type']))) ?>
                            <?php if (!empty($asset['brand_name']) || !empty($asset['model_name'])): ?>
                                (<?= htmlspecialchars(trim((string) (($asset['brand_name'] ?? '') . ' ' . ($asset['model_name'] ?? '')))) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tipo de contrato</label>
                <select name="contract_type_id">
                    <option value="">Sem contrato</option>
                    <?php foreach ($contractTypes as $contractType): ?>
                        <option value="<?= (int) $contractType['id'] ?>"><?= htmlspecialchars((string) $contractType['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Inicio</label>
                <input type="date" name="purchase_date">
            </div>
            <div>
                <label>Garantia ate</label>
                <input type="date" name="warranty_until">
            </div>
            <div>
                <label>Contrato ate</label>
                <input type="date" name="contract_until">
            </div>
            <div class="full">
                <label>Caminho do documento (nuvem)</label>
                <input type="text" name="document_path" placeholder="Ex: https://...">
            </div>
            <div class="actions-inline full">
                <button type="submit">Salvar contrato</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="card">
    <div class="table-head">
        <h2>Contratos por ativo</h2>
        <form method="get" action="index.php" class="search-form">
            <input type="hidden" name="r" value="ti.contracts">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por TAG, nome, marca, modelo, contrato ou responsavel">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>TAG</th>
                    <th>Categoria</th>
                    <th>Equipamento</th>
                    <th>Contrato</th>
                    <th>Vigencia</th>
                    <th>Propriedade</th>
                    <th>Documento</th>
                    <th>Responsavel</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $asset['tag']) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['category_name'] ?? $asset['type'])) ?></td>
                        <td>
                            <?= htmlspecialchars((string) ($asset['asset_name'] ?? '')) ?>
                            <?php if (!empty($asset['brand_name']) || !empty($asset['model_name'])): ?>
                                <br><span class="muted small"><?= htmlspecialchars(trim((string) (($asset['brand_name'] ?? '') . ' ' . ($asset['model_name'] ?? '')))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($asset['contract_type_name'] ?? '')) ?></td>
                        <td>
                            Inicio: <?= htmlspecialchars((string) ($asset['purchase_date'] ?? '')) ?><br>
                            Fim: <?= htmlspecialchars((string) ($asset['contract_until'] ?? '')) ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($asset['ownership_type'] ?? '')) ?></td>
                        <td class="break-line"><?= htmlspecialchars((string) ($asset['document_path'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($asset['staff_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="8">Nenhum contrato de ativo encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Termos de retirada (levar para casa)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TAG</th>
                    <th>Solicitante</th>
                    <th>Status</th>
                    <th>Solicitado em</th>
                    <th>Retorno previsto</th>
                    <th>Documento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>#<?= (int) $request['id'] ?></td>
                        <td><?= htmlspecialchars((string) $request['asset_tag']) ?></td>
                        <td><?= htmlspecialchars((string) $request['requester_name']) ?></td>
                        <td><?= htmlspecialchars((string) $request['status']) ?></td>
                        <td><?= htmlspecialchars((string) $request['requested_at']) ?></td>
                        <td><?= htmlspecialchars((string) ($request['due_return_date'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($request['document_text'])): ?>
                                <details>
                                    <summary>Ver termo</summary>
                                    <pre class="doc-preview"><?= htmlspecialchars((string) $request['document_text']) ?></pre>
                                </details>
                            <?php else: ?>
                                <span class="muted">Sem termo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7">Nenhum termo registrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
