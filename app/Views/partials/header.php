<?php

use App\Core\AccessControl;
use App\Core\Auth;

$title = $title ?? 'GestAll';
$currentRoute = $currentRoute ?? ($_GET['r'] ?? 'areas');
$user = Auth::user();
$hideMenu = $hideMenu ?? false;

$canTiDashboard = AccessControl::canAccessRoute('ti.dashboard', $user);
$canTiAssets = AccessControl::canAccessRoute('ti.assets', $user);
$canTiContracts = AccessControl::canAccessRoute('ti.contracts', $user);
$canTiHomeRequests = AccessControl::canAccessRoute('ti.home-requests', $user);
$canTiStaff = AccessControl::canAccessRoute('ti.staff', $user);
$canTiOperatorRequests = AccessControl::canAccessRoute('ti.operator-requests', $user);
$hasTiSection = $canTiDashboard || $canTiAssets || $canTiContracts || $canTiHomeRequests || $canTiStaff || $canTiOperatorRequests;

$canCommercialKanban = AccessControl::canAccessRoute('commercial.kanban', $user);
$canCommercialSeller = AccessControl::canAccessRoute('commercial.seller', $user);
$canCommercialCrm = AccessControl::canAccessRoute('commercial.crm', $user);
$hasCommercialSection = $canCommercialKanban || $canCommercialSeller || $canCommercialCrm;

$canPurchasesManage = AccessControl::canAccessRoute('purchases.manage', $user);
$canSettings = AccessControl::canAccessRoute('settings', $user);

$openAreas = $currentRoute === 'areas';
$openTi = in_array($currentRoute, ['ti.dashboard', 'ti.assets', 'ti.contracts', 'ti.home-requests', 'ti.staff', 'ti.operator-requests'], true);
$openCommercial = in_array($currentRoute, ['commercial.kanban', 'commercial.seller', 'commercial.crm'], true);
$openPurchases = $currentRoute === 'purchases.manage';
$openAdmin = $currentRoute === 'settings';
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

            <div class="nav-section module-nav" data-module="areas">
                <button type="button" class="section-toggle" data-module-toggle="areas" aria-expanded="<?= $openAreas ? 'true' : 'false' ?>">
                    <span class="section-title">Areas</span>
                    <span class="section-arrow"><?= $openAreas ? 'v' : '>' ?></span>
                </button>
                <div class="section-body <?= $openAreas ? '' : 'collapsed' ?>" data-module-body="areas">
                    <a class="nav-link <?= $currentRoute === 'areas' ? 'active' : '' ?>" href="index.php?r=areas">Visao geral</a>
                </div>
            </div>

            <?php if ($hasTiSection): ?>
            <div class="nav-section module-nav" data-module="ti">
                <button type="button" class="section-toggle" data-module-toggle="ti" aria-expanded="<?= $openTi ? 'true' : 'false' ?>">
                    <span class="section-title">Gerenciamento da TI</span>
                    <span class="section-arrow"><?= $openTi ? 'v' : '>' ?></span>
                </button>
                <div class="section-body <?= $openTi ? '' : 'collapsed' ?>" data-module-body="ti">
                <?php if ($canTiDashboard): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.dashboard' ? 'active' : '' ?>" href="index.php?r=ti.dashboard">Dashboard TI</a>
                <?php endif; ?>
                <?php if ($canTiAssets): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.assets' ? 'active' : '' ?>" href="index.php?r=ti.assets">Ativos gerais</a>
                <?php endif; ?>
                <?php if ($canTiContracts): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.contracts' ? 'active' : '' ?>" href="index.php?r=ti.contracts">Contratos e termos</a>
                <?php endif; ?>
                <?php if ($canTiHomeRequests): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.home-requests' ? 'active' : '' ?>" href="index.php?r=ti.home-requests">Pedido casa</a>
                <?php endif; ?>
                <?php if ($canTiStaff): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.staff' ? 'active' : '' ?>" href="index.php?r=ti.staff">Colaboradores</a>
                <?php endif; ?>
                <?php if ($canTiOperatorRequests): ?>
                    <a class="nav-link <?= $currentRoute === 'ti.operator-requests' ? 'active' : '' ?>" href="index.php?r=ti.operator-requests">Pedidos operadores</a>
                <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($hasCommercialSection): ?>
            <div class="nav-section module-nav" data-module="comercial">
                <button type="button" class="section-toggle" data-module-toggle="comercial" aria-expanded="<?= $openCommercial ? 'true' : 'false' ?>">
                    <span class="section-title">Gerenciamento Comercial</span>
                    <span class="section-arrow"><?= $openCommercial ? 'v' : '>' ?></span>
                </button>
                <div class="section-body <?= $openCommercial ? '' : 'collapsed' ?>" data-module-body="comercial">
                <?php if ($canCommercialKanban): ?>
                    <a class="nav-link <?= $currentRoute === 'commercial.kanban' ? 'active' : '' ?>" href="index.php?r=commercial.kanban">Kanban Comercial</a>
                <?php endif; ?>
                <?php if ($canCommercialCrm): ?>
                    <a class="nav-link <?= $currentRoute === 'commercial.crm' ? 'active' : '' ?>" href="index.php?r=commercial.crm">CRM Comercial</a>
                <?php endif; ?>
                <?php if ($canCommercialSeller): ?>
                    <a class="nav-link <?= $currentRoute === 'commercial.seller' ? 'active' : '' ?>" href="index.php?r=commercial.seller">Vendedor</a>
                <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($canPurchasesManage): ?>
            <div class="nav-section module-nav" data-module="compras">
                <button type="button" class="section-toggle" data-module-toggle="compras" aria-expanded="<?= $openPurchases ? 'true' : 'false' ?>">
                    <span class="section-title">Gerenciamento de Compras</span>
                    <span class="section-arrow"><?= $openPurchases ? 'v' : '>' ?></span>
                </button>
                <div class="section-body <?= $openPurchases ? '' : 'collapsed' ?>" data-module-body="compras">
                    <a class="nav-link <?= $currentRoute === 'purchases.manage' ? 'active' : '' ?>" href="index.php?r=purchases.manage">Compras</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($canSettings): ?>
            <div class="nav-section module-nav" data-module="admin">
                <button type="button" class="section-toggle" data-module-toggle="admin" aria-expanded="<?= $openAdmin ? 'true' : 'false' ?>">
                    <span class="section-title">Administracao</span>
                    <span class="section-arrow"><?= $openAdmin ? 'v' : '>' ?></span>
                </button>
                <div class="section-body <?= $openAdmin ? '' : 'collapsed' ?>" data-module-body="admin">
                    <a class="nav-link <?= $currentRoute === 'settings' ? 'active' : '' ?>" href="index.php?r=settings">Configuracoes</a>
                </div>
            </div>
            <?php endif; ?>

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
