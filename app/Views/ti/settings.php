<section class="page-head">
    <h1>Configuracoes TI</h1>
    <p>Cadastros base usados no controle de ativos e contratos.</p>
</section>

<?php if ((string) $success === '1'): ?>
    <div class="alert success">Cadastro salvo com sucesso.</div>
<?php elseif ((string) $success === '2'): ?>
    <div class="alert success">Item atualizado com sucesso.</div>
<?php elseif ((string) $success === '3'): ?>
    <div class="alert success">Item removido com sucesso.</div>
<?php endif; ?>
<?php if ((string) $error === '1'): ?>
    <div class="alert error">Informe um nome valido.</div>
<?php elseif ((string) $error === '2'): ?>
    <div class="alert error">Nao foi possivel salvar. Talvez o item ja exista.</div>
<?php endif; ?>

<section class="card-grid triple">
    <article class="card">
        <h2>Categorias de equipamento</h2>
        <form method="post" action="index.php?r=ti.settings.categories.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Impressora" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingCategory): ?>
            <form method="post" action="index.php?r=ti.settings.categories.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingCategory['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingCategory['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=ti.settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($categories as $category): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars($category['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=ti.settings&category_edit=<?= (int) $category['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=ti.settings.categories.delete" onsubmit="return confirm('Deseja excluir esta categoria?');">
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
        <form method="post" action="index.php?r=ti.settings.contract-types.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Estagio" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingContractType): ?>
            <form method="post" action="index.php?r=ti.settings.contract-types.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingContractType['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingContractType['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=ti.settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($contractTypes as $contractType): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars($contractType['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=ti.settings&contract_edit=<?= (int) $contractType['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=ti.settings.contract-types.delete" onsubmit="return confirm('Deseja excluir este tipo de contrato?');">
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
        <form method="post" action="index.php?r=ti.settings.statuses.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Em manutencao" required>
            <button type="submit">Adicionar</button>
        </form>
        <?php if ($editingStatus): ?>
            <form method="post" action="index.php?r=ti.settings.statuses.update" class="inline-form">
                <input type="hidden" name="id" value="<?= (int) $editingStatus['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars((string) $editingStatus['name']) ?>" required>
                <button type="submit">Salvar</button>
            </form>
            <a class="btn btn-muted" href="index.php?r=ti.settings">Cancelar edicao</a>
        <?php endif; ?>
        <ul class="simple-list stack-list">
            <?php foreach ($statuses as $status): ?>
                <li class="stack-item">
                    <span><?= htmlspecialchars($status['name']) ?></span>
                    <div class="actions-inline">
                        <a class="btn btn-muted" href="index.php?r=ti.settings&status_edit=<?= (int) $status['id'] ?>">Editar</a>
                        <form method="post" action="index.php?r=ti.settings.statuses.delete" onsubmit="return confirm('Deseja excluir este status?');">
                            <input type="hidden" name="id" value="<?= (int) $status['id'] ?>">
                            <button class="btn-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
