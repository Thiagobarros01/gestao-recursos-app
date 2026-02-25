<section class="page-head">
    <h1>Gerenciamento da TI</h1>
    <p>Painel rapido para ativos e contratos de TI.</p>
</section>

<section class="stats-grid">
    <article class="stat-card"><p>Total de ativos</p><h3><?= (int) $totalAssets ?></h3></article>
    <article class="stat-card"><p>Colaboradores</p><h3><?= (int) $totalStaff ?></h3></article>
    <article class="stat-card"><p>Categorias</p><h3><?= (int) $totalCategories ?></h3></article>
    <article class="stat-card"><p>Tipos de contrato</p><h3><?= (int) $totalContracts ?></h3></article>
    <article class="stat-card warn"><p>Em uso</p><h3><?= (int) $emUso ?></h3></article>
    <article class="stat-card warn"><p>Sem responsavel</p><h3><?= (int) $unassignedAssets ?></h3></article>
    <article class="stat-card warn"><p>Pedidos pendentes</p><h3><?= (int) $homeRequestsPending ?></h3></article>
    <article class="stat-card warn"><p>Em uso externo</p><h3><?= (int) $homeRequestsApproved ?></h3></article>
    <article class="stat-card ok"><p>Devolvido</p><h3><?= (int) $devolvido ?></h3></article>
    <article class="stat-card danger"><p>Perda</p><h3><?= (int) $perda ?></h3></article>
    <article class="stat-card danger"><p>Roubo</p><h3><?= (int) $roubo ?></h3></article>
</section>

<section class="card-grid">
    <article class="module-card">
        <h2>Ativos gerais</h2>
        <p>Cadastre e organize equipamentos por propriedade, setor, rede/IP e responsavel.</p>
        <a class="btn" href="index.php?r=ti.assets">Abrir modulo</a>
    </article>
    <article class="module-card">
        <h2>Contratos e termos</h2>
        <p>Veja vigencias, documentos em nuvem e termos de retirada em um lugar unico.</p>
        <a class="btn" href="index.php?r=ti.contracts">Abrir modulo</a>
    </article>
    <article class="module-card">
        <h2>Configuracoes TI</h2>
        <p>Gerencie categorias, contratos e status usados pelos ativos.</p>
        <a class="btn" href="index.php?r=settings">Abrir configuracoes</a>
    </article>
</section>
