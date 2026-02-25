<section class="page-head">
    <h1>Contratos e termos</h1>
    <p>Controle de vigencias, documentos em nuvem e termos de retirada.</p>
</section>

<section class="card">
    <div class="table-head">
        <h2>Contratos por ativo</h2>
        <form method="get" action="index.php" class="search-form">
            <input type="hidden" name="r" value="ti.contracts">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por TAG, serial, contrato, documento ou responsavel">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>TAG</th>
                    <th>Categoria</th>
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
                    <tr><td colspan="7">Nenhum contrato de ativo encontrado.</td></tr>
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
