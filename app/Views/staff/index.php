<section class="page-head">
    <h1>Colaboradores de TI</h1>
    <p>Base de usuarios internos para vincular ativos e contratos.</p>
</section>

<section class="card split">
    <div>
        <h2>Novo colaborador</h2>
        <?php if (!empty($success)): ?>
            <div class="alert success">Colaborador cadastrado com sucesso.</div>
        <?php endif; ?>
        <form method="post" action="index.php?r=ti.staff.store" class="form-grid two">
            <input type="text" name="name" placeholder="Nome" required>
            <input type="email" name="email" placeholder="Email">
            <input type="text" name="department" placeholder="Departamento">
            <button type="submit">Salvar colaborador</button>
        </form>
    </div>
</section>

<section class="card">
    <h2>Lista de colaboradores</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Departamento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $person): ?>
                    <tr>
                        <td><?= htmlspecialchars($person['name']) ?></td>
                        <td><?= htmlspecialchars((string) $person['email']) ?></td>
                        <td><?= htmlspecialchars((string) $person['department']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($staff)): ?>
                    <tr><td colspan="3">Nenhum colaborador cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
