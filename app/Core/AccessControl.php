<?php

declare(strict_types=1);

namespace App\Core;

final class AccessControl
{
    public static function isFullAccess(?array $user): bool
    {
        $role = self::normalizeRole($user['role'] ?? null);
        return in_array($role, ['admin', 'ti'], true);
    }

    public static function normalizeRole(mixed $role): string
    {
        $value = strtolower(trim((string) $role));
        if (!in_array($value, ['admin', 'ti', 'gestor', 'operador'], true)) {
            return 'operador';
        }

        return $value;
    }

    public static function departmentScope(?array $user): ?string
    {
        if (self::isFullAccess($user)) {
            return null;
        }

        $department = trim((string) ($user['department_name'] ?? ''));
        return $department !== '' ? $department : '__none__';
    }

    public static function staffScope(?array $user): ?int
    {
        if (self::isFullAccess($user)) {
            return null;
        }

        $role = self::normalizeRole($user['role'] ?? null);
        if ($role !== 'operador') {
            return null;
        }

        $staffId = (int) ($user['staff_id'] ?? 0);
        return $staffId > 0 ? $staffId : -1;
    }

    public static function canAccessRoute(string $route, ?array $user): bool
    {
        if ($route === 'logout' || $route === 'areas') {
            return true;
        }

        if (str_starts_with($route, 'settings')) {
            return self::isAdmin($user);
        }

        if (str_starts_with($route, 'commercial.seller') || str_starts_with($route, 'purchases.seller')) {
            if (!$user) {
                return false;
            }
            if (self::isFullAccess($user)) {
                return true;
            }
            return (bool) ($user['is_seller'] ?? false);
        }

        if ($user && self::normalizeRole($user['role'] ?? null) === 'operador' && (bool) ($user['is_seller'] ?? false)) {
            return in_array($route, [
                'commercial.kanban',
                'commercial.kanban.task.move',
                'commercial.kanban.task.update',
                'commercial.kanban.comment.store',
                'commercial.kanban.comment.delete',
                'commercial.crm',
                'commercial.crm.client',
                'commercial.crm.kanban',
                'commercial.crm.client.store',
                'commercial.crm.sale.store',
                'commercial.crm.contact.store',
                'commercial.crm.kanban.stage.update',
                'commercial.crm.import',
                'commercial.crm.import.template',
            ], true);
        }

        if (self::isFullAccess($user)) {
            return true;
        }

        $role = self::normalizeRole($user['role'] ?? null);
        $allowed = self::baseRoutesForRole($role, $user);

        $extra = $user['allowed_routes'] ?? [];
        if (is_array($extra)) {
            foreach ($extra as $item) {
                $routeName = trim((string) $item);
                if ($routeName !== '') {
                    $allowed[] = $routeName;
                }
            }
        }

        if (in_array($route, ['dashboard', 'assets', 'staff'], true)) {
            return true;
        }

        return in_array($route, array_unique($allowed), true);
    }

    public static function permissionGroups(): array
    {
        return [
            'ti_dashboard' => [
                'label' => 'Dashboard TI',
                'routes' => ['ti.dashboard'],
            ],
            'ti_assets' => [
                'label' => 'Ativos TI',
                'routes' => [
                    'ti.assets',
                    'ti.assets.store',
                    'ti.assets.update',
                    'ti.assets.delete',
                    'ti.assets.transfer',
                    'ti.assets.quick-department.store',
                    'ti.assets.quick-staff.store',
                ],
            ],
            'ti_home_requests' => [
                'label' => 'Pedido Levar Casa',
                'routes' => [
                    'ti.home-requests',
                    'ti.home-requests.store',
                    'ti.home-requests.approve',
                    'ti.home-requests.reject',
                    'ti.home-requests.return',
                ],
            ],
            'ti_contracts' => [
                'label' => 'Contratos e Termos',
                'routes' => ['ti.contracts', 'ti.contracts.update'],
            ],
            'ti_staff' => [
                'label' => 'Colaboradores TI',
                'routes' => ['ti.staff', 'ti.staff.store', 'ti.staff.update', 'ti.staff.delete'],
            ],
            'settings_admin' => [
                'label' => 'Configuracoes Admin',
                'routes' => [
                    'settings',
                    'settings.categories.store',
                    'settings.categories.update',
                    'settings.categories.delete',
                    'settings.contract-types.store',
                    'settings.contract-types.update',
                    'settings.contract-types.delete',
                    'settings.statuses.store',
                    'settings.statuses.update',
                    'settings.statuses.delete',
                    'settings.departments.store',
                    'settings.departments.update',
                    'settings.departments.delete',
                    'settings.users.store',
                    'settings.users.update',
                    'settings.staff.store',
                    'settings.staff.update',
                    'settings.crm.clients.store',
                    'settings.crm.clients.update',
                    'settings.products.store',
                    'settings.products.update',
                    'settings.crm.update',
                ],
            ],
            'commercial_kanban' => [
                'label' => 'Kanban Comercial',
                'routes' => [
                    'commercial.kanban',
                    'commercial.kanban.board.store',
                    'commercial.kanban.board.update',
                    'commercial.kanban.board.archive',
                    'commercial.kanban.board.unarchive',
                    'commercial.kanban.board.delete',
                    'commercial.kanban.members.update',
                    'commercial.kanban.task.store',
                    'commercial.kanban.task.move',
                    'commercial.kanban.task.update',
                    'commercial.kanban.task.delete',
                    'commercial.kanban.comment.store',
                    'commercial.kanban.comment.delete',
                ],
            ],
            'purchases_manage' => [
                'label' => 'Compras - Gestao',
                'routes' => [
                    'purchases.manage',
                    'purchases.products.store',
                    'purchases.products.update-stock',
                    'purchases.shortages.accept',
                    'purchases.shortages.resolve',
                    'purchases.shortages.close',
                    'purchases.shortages.resolve-all',
                    'purchases.shortages.close-all',
                ],
            ],
            'commercial_seller' => [
                'label' => 'Comercial - Vendedor',
                'routes' => [
                    'commercial.seller',
                    'commercial.seller.shortage.store',
                    'commercial.seller.ti-request.store',
                ],
            ],
            'commercial_crm' => [
                'label' => 'Comercial - CRM',
                'routes' => [
                    'commercial.crm',
                    'commercial.crm.client',
                    'commercial.crm.kanban',
                    'commercial.crm.client.store',
                    'commercial.crm.sale.store',
                    'commercial.crm.contact.store',
                    'commercial.crm.kanban.stage.update',
                    'commercial.crm.import',
                    'commercial.crm.import.template',
                ],
            ],
            'ti_operator_requests' => [
                'label' => 'TI - Pedidos Operadores',
                'routes' => [
                    'ti.operator-requests',
                    'ti.operator-requests.approve',
                    'ti.operator-requests.reject',
                ],
            ],
        ];
    }

    public static function routesFromGroupKeys(array $keys): array
    {
        $catalog = self::permissionGroups();
        $routes = [];

        foreach ($keys as $key) {
            $key = (string) $key;
            if (!isset($catalog[$key])) {
                continue;
            }

            foreach ($catalog[$key]['routes'] as $route) {
                $routes[] = $route;
            }
        }

        return array_values(array_unique($routes));
    }

    private static function baseRoutesForRole(string $role, ?array $user = null): array
    {
        if ($role === 'gestor') {
            return [];
        }

        if ($role === 'operador') {
            $routes = [];
            if ((bool) ($user['is_seller'] ?? false)) {
                $routes[] = 'commercial.seller';
                $routes[] = 'commercial.seller.shortage.store';
                $routes[] = 'commercial.seller.ti-request.store';
                $routes[] = 'commercial.crm';
                $routes[] = 'commercial.crm.client';
                $routes[] = 'commercial.crm.kanban';
                $routes[] = 'commercial.crm.client.store';
                $routes[] = 'commercial.crm.sale.store';
                $routes[] = 'commercial.crm.contact.store';
                $routes[] = 'commercial.crm.kanban.stage.update';
                $routes[] = 'commercial.crm.import';
                $routes[] = 'commercial.crm.import.template';
            }
            return $routes;
        }

        return [];
    }

    private static function isAdmin(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        return self::normalizeRole($user['role'] ?? null) === 'admin';
    }
}
