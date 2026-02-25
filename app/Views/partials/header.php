<?php

use App\Core\AccessControl;
use App\Core\Auth;

$title = $title ?? 'GestAll';
$currentRoute = $currentRoute ?? ($_GET['r'] ?? 'areas');
$user = Auth::user();
$hideMenu = $hideMenu ?? false;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> | GestAll</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if (!$hideMenu): ?>
    <button class="menu-toggle" id="menuToggle" type="button">Menu</button>
<?php endif; ?>
<div class="layout">
    <?php if (!$hideMenu): ?>
        <aside class="sidebar" id="sidebar">
            <div class="brand-block">
                <p class="brand-title">GestAll</p>
                <p class="brand-subtitle">Controle de Gestao</p>
            </div>

            <div class="nav-section">
                <p class="section-title">Areas</p>
                <a class="nav-link <?= $currentRoute === 'areas' ? 'active' : '' ?>" href="index.php?r=areas">Visao geral</a>
            </div>

            <div class="nav-section">
                <p class="section-title">Gerenciamento da TI</p>
                <?php if (AccessControl::canAccessRoute('ti.dashboard', $user)): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.dashboard' ? 'active' : '' ?>" href="index.php?r=ti.dashboard">Dashboard TI</a>
                <?php endif; ?>
                <?php if (AccessControl::canAccessRoute('ti.assets', $user)): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.assets' ? 'active' : '' ?>" href="index.php?r=ti.assets">Ativos e contratos</a>
                <?php endif; ?>
                <?php if (AccessControl::canAccessRoute('ti.home-requests', $user)): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.home-requests' ? 'active' : '' ?>" href="index.php?r=ti.home-requests">Pedido casa</a>
                <?php endif; ?>
                <?php if (AccessControl::canAccessRoute('ti.staff', $user)): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.staff' ? 'active' : '' ?>" href="index.php?r=ti.staff">Colaboradores</a>
                <?php endif; ?>
                <?php if (AccessControl::canAccessRoute('ti.settings', $user)): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.settings' ? 'active' : '' ?>" href="index.php?r=ti.settings">Configuracoes TI</a>
                <?php endif; ?>
            </div>

            <div class="nav-section muted">
                <p class="section-title">Proximas areas</p>
                <span class="nav-link disabled">Gerenciamento comercial</span>
            </div>

            <?php if ($user): ?>
                <div class="user-box">
                    <p class="user-name"><?= htmlspecialchars($user['name']) ?></p>
                    <p class="user-login">@<?= htmlspecialchars($user['username']) ?></p>
                    <p class="user-login"><?= htmlspecialchars((string) ($user['role'] ?? '')) ?><?= !empty($user['department_name']) ? ' - ' . htmlspecialchars((string) $user['department_name']) : '' ?></p>
                    <a class="logout-link" href="index.php?r=logout">Sair</a>
                </div>
            <?php endif; ?>
        </aside>
    <?php endif; ?>
    <main class="content">
