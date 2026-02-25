<section class="page-head">
    <h1>Colaboradores de TI</h1>
    <p>Base de usuarios internos para vincular ativos e contratos.</p>
</section>

<section class="card split">
    <div>
        <h2><?= $editingStaff ? 'Editar colaborador' : 'Novo colaborador' ?></h2>
        <?php if ((string) $success === '1'): ?>
            <div class="alert success">Colaborador cadastrado com sucesso.</div>
        <?php elseif ((string) $success === '2'): ?>
            <div class="alert success">Colaborador atualizado com sucesso.</div>
        <?php elseif ((string) $success === '3'): ?>
            <div class="alert success">Colaborador removido com sucesso.</div>
        <?php endif; ?>
        <?php if ((string) $error === '1'): ?>
            <div class="alert error">Dados invalidos para salvar o colaborador.</div>
        <?php elseif ((string) $error === '2'): ?>
            <div class="alert error">Nao foi possivel salvar/excluir o colaborador.</div>
        <?php endif; ?>
        <form method="post" action="index.php?r=<?= $editingStaff ? 'ti.staff.update' : 'ti.staff.store' ?>" class="form-grid two">
            <?php if ($editingStaff): ?>
                <input type="hidden" name="id" value="<?= (int) $editingStaff['id'] ?>">
            <?php endif; ?>
            <input type="text" name="name" placeholder="Nome" value="<?= htmlspecialchars((string) ($editingStaff['name'] ?? '')) ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars((string) ($editingStaff['email'] ?? '')) ?>">
            <input type="text" name="department" placeholder="Departamento" value="<?= htmlspecialchars((string) ($editingStaff['department'] ?? '')) ?>">
            <div class="actions-inline">
                <button type="submit"><?= $editingStaff ? 'Atualizar colaborador' : 'Salvar colaborador' ?></button>
                <?php if ($editingStaff): ?>
                    <a class="btn btn-muted" href="index.php?r=ti.staff">Cancelar edicao</a>
                <?php endif; ?>
            </div>
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
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $person): ?>
                    <tr>
                        <td><?= htmlspecialchars($person['name']) ?></td>
                        <td><?= htmlspecialchars((string) $person['email']) ?></td>
                        <td><?= htmlspecialchars((string) $person['department']) ?></td>
                        <td>
                            <div class="actions-inline">
                                <a class="btn btn-muted" href="index.php?r=ti.staff&edit=<?= (int) $person['id'] ?>">Editar</a>
                                <form method="post" action="index.php?r=ti.staff.delete" onsubmit="return confirm('Deseja excluir este colaborador?');">
                                    <input type="hidden" name="id" value="<?= (int) $person['id'] ?>">
                                    <button class="btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($staff)): ?>
                    <tr><td colspan="4">Nenhum colaborador cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
