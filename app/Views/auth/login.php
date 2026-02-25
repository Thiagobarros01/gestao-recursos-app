<section class="login-shell">
    <section class="login-card">
        <p class="pill">GestAll</p>
        <h1>Acesso ao sistema</h1>
        <p>Entre para acessar os modulos de gerenciamento.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?r=login.submit" class="form-grid">
            <div>
                <label>Usuario</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>

        <p class="muted small">Acesso inicial: admin / admin123</p>
    </section>
</section>
