<section class="page-head">
    <h1>Configuracoes TI</h1>
    <p>Cadastros base usados no controle de ativos e contratos.</p>
</section>

<?php if (!empty($success)): ?>
    <div class="alert success">Cadastro salvo com sucesso.</div>
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
        <ul class="simple-list">
            <?php foreach ($categories as $category): ?>
                <li><?= htmlspecialchars($category['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Tipos de contrato</h2>
        <form method="post" action="index.php?r=ti.settings.contract-types.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Estagio" required>
            <button type="submit">Adicionar</button>
        </form>
        <ul class="simple-list">
            <?php foreach ($contractTypes as $contractType): ?>
                <li><?= htmlspecialchars($contractType['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Status do ativo</h2>
        <form method="post" action="index.php?r=ti.settings.statuses.store" class="inline-form">
            <input type="text" name="name" placeholder="Ex: Em manutencao" required>
            <button type="submit">Adicionar</button>
        </form>
        <ul class="simple-list">
            <?php foreach ($statuses as $status): ?>
                <li><?= htmlspecialchars($status['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
