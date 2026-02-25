<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class SchemaService
{
    public static function migrate(PDO $pdo, array $config): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT \'admin\',
                department_id INTEGER,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_route_permissions (
                user_id INTEGER NOT NULL,
                route TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, route),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS staff (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT,
                department TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS equipment_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS contract_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS asset_statuses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                sort_order INTEGER NOT NULL DEFAULT 100,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS assets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT,
                tag TEXT NOT NULL UNIQUE,
                serial_number TEXT,
                status TEXT,
                condition_state TEXT,
                notes TEXT,
                document_path TEXT,
                staff_id INTEGER,
                category_id INTEGER,
                contract_type_id INTEGER,
                status_id INTEGER,
                observation TEXT,
                purchase_date TEXT,
                warranty_until TEXT,
                contract_until TEXT,
                returned_at TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
                FOREIGN KEY (category_id) REFERENCES equipment_categories(id) ON DELETE SET NULL,
                FOREIGN KEY (contract_type_id) REFERENCES contract_types(id) ON DELETE SET NULL,
                FOREIGN KEY (status_id) REFERENCES asset_statuses(id) ON DELETE SET NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS home_equipment_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                asset_id INTEGER NOT NULL,
                requester_staff_id INTEGER NOT NULL,
                requester_name TEXT NOT NULL,
                reason TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'pending\',
                requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                approved_by TEXT,
                approved_at TEXT,
                due_return_date TEXT,
                returned_at TEXT,
                condition_out TEXT,
                condition_in TEXT,
                document_text TEXT,
                FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
                FOREIGN KEY (requester_staff_id) REFERENCES staff(id) ON DELETE RESTRICT
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS asset_movements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                asset_id INTEGER NOT NULL,
                movement_type TEXT NOT NULL,
                details TEXT,
                from_status TEXT,
                to_status TEXT,
                from_staff TEXT,
                to_staff TEXT,
                changed_by TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
            )'
        );

        self::ensureAssetColumn($pdo, 'category_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'contract_type_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'status_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'observation', 'TEXT');
        self::ensureAssetColumn($pdo, 'purchase_date', 'TEXT');
        self::ensureAssetColumn($pdo, 'warranty_until', 'TEXT');
        self::ensureAssetColumn($pdo, 'contract_until', 'TEXT');
        self::ensureAssetColumn($pdo, 'returned_at', 'TEXT');
        self::ensureAssetColumn($pdo, 'condition_state', 'TEXT');
        self::ensureUserColumn($pdo, 'role', 'TEXT NOT NULL DEFAULT \'admin\'');
        self::ensureUserColumn($pdo, 'department_id', 'INTEGER');

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_type ON assets(type)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status ON assets(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_staff ON assets(staff_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_category ON assets(category_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_contract ON assets(contract_type_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status_id ON assets(status_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_warranty_until ON assets(warranty_until)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_contract_until ON assets(contract_until)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_movements_asset_date ON asset_movements(asset_id, created_at DESC)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_home_req_asset ON home_equipment_requests(asset_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_home_req_status ON home_equipment_requests(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_home_req_requester ON home_equipment_requests(requester_staff_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_department ON users(department_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_perm_user ON user_route_permissions(user_id)');

        self::seedDefaults($pdo);

        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, name, role, department_id)
                 VALUES (:username, :password_hash, :name, :role, :department_id)'
            );
            $stmt->execute([
                ':username' => $config['default_admin']['username'],
                ':password_hash' => password_hash($config['default_admin']['password'], PASSWORD_DEFAULT),
                ':name' => $config['default_admin']['name'],
                ':role' => 'admin',
                ':department_id' => null,
            ]);
        }
    }

    private static function ensureAssetColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(assets)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }

        $pdo->exec(sprintf('ALTER TABLE assets ADD COLUMN %s %s', $column, $definition));
    }

    private static function ensureUserColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(users)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }

        $pdo->exec(sprintf('ALTER TABLE users ADD COLUMN %s %s', $column, $definition));
    }

    private static function seedDefaults(PDO $pdo): void
    {
        self::seedTable($pdo, 'equipment_categories', ['Notebook', 'Tablet', 'Desktop', 'Monitor', 'Celular']);
        self::seedTable($pdo, 'contract_types', ['Comodato', 'CLT', 'Terceiro']);
        self::seedTable($pdo, 'departments', ['TI']);

        $statusRows = [
            ['name' => 'Em uso', 'sort_order' => 1],
            ['name' => 'Devolvido', 'sort_order' => 2],
            ['name' => 'Perda', 'sort_order' => 3],
            ['name' => 'Roubo', 'sort_order' => 4],
        ];

        $stmt = $pdo->prepare('INSERT OR IGNORE INTO asset_statuses (name, sort_order) VALUES (:name, :sort_order)');
        foreach ($statusRows as $row) {
            $stmt->execute([
                ':name' => $row['name'],
                ':sort_order' => $row['sort_order'],
            ]);
        }
    }

    private static function seedTable(PDO $pdo, string $table, array $values): void
    {
        $stmt = $pdo->prepare(sprintf('INSERT OR IGNORE INTO %s (name) VALUES (:name)', $table));
        foreach ($values as $name) {
            $stmt->execute([':name' => $name]);
        }
    }
}
