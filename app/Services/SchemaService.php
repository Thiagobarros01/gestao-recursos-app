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
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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
                notes TEXT,
                document_path TEXT,
                staff_id INTEGER,
                category_id INTEGER,
                contract_type_id INTEGER,
                status_id INTEGER,
                observation TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
                FOREIGN KEY (category_id) REFERENCES equipment_categories(id) ON DELETE SET NULL,
                FOREIGN KEY (contract_type_id) REFERENCES contract_types(id) ON DELETE SET NULL,
                FOREIGN KEY (status_id) REFERENCES asset_statuses(id) ON DELETE SET NULL
            )'
        );

        self::ensureAssetColumn($pdo, 'category_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'contract_type_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'status_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'observation', 'TEXT');

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_type ON assets(type)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status ON assets(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_staff ON assets(staff_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_category ON assets(category_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_contract ON assets(contract_type_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status_id ON assets(status_id)');

        self::seedDefaults($pdo);

        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, name) VALUES (:username, :password_hash, :name)');
            $stmt->execute([
                ':username' => $config['default_admin']['username'],
                ':password_hash' => password_hash($config['default_admin']['password'], PASSWORD_DEFAULT),
                ':name' => $config['default_admin']['name'],
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

    private static function seedDefaults(PDO $pdo): void
    {
        self::seedTable($pdo, 'equipment_categories', ['Notebook', 'Tablet', 'Desktop', 'Monitor', 'Celular']);
        self::seedTable($pdo, 'contract_types', ['Comodato', 'CLT', 'Terceiro']);

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
