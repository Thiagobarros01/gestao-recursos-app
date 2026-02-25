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

        if (self::isFullAccess($user)) {
            return true;
        }

        $role = self::normalizeRole($user['role'] ?? null);
        $allowed = self::baseRoutesForRole($role);

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
            'ti_settings' => [
                'label' => 'Configuracoes TI',
                'routes' => [
                    'ti.settings',
                    'ti.settings.categories.store',
                    'ti.settings.categories.update',
                    'ti.settings.categories.delete',
                    'ti.settings.contract-types.store',
                    'ti.settings.contract-types.update',
                    'ti.settings.contract-types.delete',
                    'ti.settings.statuses.store',
                    'ti.settings.statuses.update',
                    'ti.settings.statuses.delete',
                    'ti.settings.departments.store',
                    'ti.settings.departments.update',
                    'ti.settings.departments.delete',
                    'ti.settings.users.store',
                    'ti.settings.users.update',
                ],
            ],
            'commercial_kanban' => [
                'label' => 'Kanban Comercial',
                'routes' => [
                    'commercial.kanban',
                    'commercial.kanban.board.store',
                    'commercial.kanban.board.update',
                    'commercial.kanban.members.update',
                    'commercial.kanban.task.store',
                    'commercial.kanban.task.move',
                    'commercial.kanban.task.update',
                    'commercial.kanban.task.delete',
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

    private static function baseRoutesForRole(string $role): array
    {
        if ($role === 'gestor') {
            return [
                'ti.dashboard',
                'ti.assets',
                'ti.assets.quick-department.store',
                'ti.assets.quick-staff.store',
                'ti.contracts',
                'ti.home-requests',
                'commercial.kanban',
                'commercial.kanban.board.store',
                'commercial.kanban.board.update',
                'commercial.kanban.members.update',
                'commercial.kanban.task.store',
                'commercial.kanban.task.move',
                'commercial.kanban.task.update',
                'commercial.kanban.task.delete',
            ];
        }

        if ($role === 'operador') {
            return [
                'ti.dashboard',
                'ti.assets',
                'ti.contracts',
                'ti.home-requests',
                'ti.home-requests.store',
                'commercial.kanban',
                'commercial.kanban.task.move',
                'commercial.kanban.task.update',
            ];
        }

        return [];
    }
}
