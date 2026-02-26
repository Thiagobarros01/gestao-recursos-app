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
                staff_id INTEGER,
                is_seller INTEGER NOT NULL DEFAULT 0,
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
                ownership_type TEXT,
                department_id INTEGER,
                network_mode TEXT,
                ip_address TEXT,
                asset_name TEXT,
                brand_name TEXT,
                model_name TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
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

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS commercial_boards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                owner_user_id INTEGER NOT NULL,
                is_archived INTEGER NOT NULL DEFAULT 0,
                archived_at TEXT,
                stage_name_todo TEXT,
                stage_name_doing TEXT,
                stage_name_review TEXT,
                stage_name_done TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS commercial_board_members (
                board_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (board_id, user_id),
                FOREIGN KEY (board_id) REFERENCES commercial_boards(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS commercial_tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                board_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                customer_name TEXT,
                company_name TEXT,
                deal_value REAL,
                tag_name TEXT,
                status TEXT NOT NULL DEFAULT \'todo\',
                priority TEXT NOT NULL DEFAULT \'media\',
                assignee_user_id INTEGER,
                due_date TEXT,
                created_by_user_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (board_id) REFERENCES commercial_boards(id) ON DELETE CASCADE,
                FOREIGN KEY (assignee_user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS commercial_task_comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                comment_text TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (task_id) REFERENCES commercial_tasks(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_user_id INTEGER NOT NULL,
                erp_customer_code TEXT,
                seller_code TEXT,
                client_name TEXT NOT NULL,
                company_name TEXT,
                phone TEXT,
                whatsapp TEXT,
                neighborhood TEXT,
                birth_date TEXT,
                email TEXT,
                status TEXT NOT NULL DEFAULT \'ativo\',
                notes TEXT,
                last_purchase_date TEXT,
                total_spent REAL NOT NULL DEFAULT 0,
                purchase_count INTEGER NOT NULL DEFAULT 0,
                ticket_avg REAL NOT NULL DEFAULT 0,
                days_without_purchase INTEGER,
                status_customer TEXT NOT NULL DEFAULT \'NOVO\',
                crm_kanban_stage TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_sales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                seller_user_id INTEGER NOT NULL,
                order_number TEXT,
                invoice_number TEXT,
                sale_date TEXT NOT NULL,
                amount REAL NOT NULL DEFAULT 0,
                payment_method TEXT,
                products_text TEXT,
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES crm_clients(id) ON DELETE CASCADE,
                FOREIGN KEY (seller_user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_settings (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                followup_after_days INTEGER NOT NULL DEFAULT 30,
                vip_amount_threshold REAL NOT NULL DEFAULT 1000,
                inactive_after_days INTEGER NOT NULL DEFAULT 60,
                active_after_days INTEGER NOT NULL DEFAULT 30,
                new_after_days INTEGER NOT NULL DEFAULT 30,
                recurrence_window_days INTEGER NOT NULL DEFAULT 90,
                recurrence_min_purchases INTEGER NOT NULL DEFAULT 3,
                auto_status_enabled INTEGER NOT NULL DEFAULT 1,
                updated_by_user_id INTEGER,
                updated_at TEXT,
                FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_contact_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                contact_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                contact_type TEXT NOT NULL,
                notes TEXT,
                user_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES crm_clients(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_loyalty (
                client_id INTEGER PRIMARY KEY,
                points INTEGER NOT NULL DEFAULT 0,
                updated_at TEXT,
                FOREIGN KEY (client_id) REFERENCES crm_clients(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS crm_client_tags (
                client_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (client_id, tag_id),
                FOREIGN KEY (client_id) REFERENCES crm_clients(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES crm_tags(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS purchase_products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                sku TEXT,
                stock_qty INTEGER NOT NULL DEFAULT 0,
                min_qty INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS purchase_shortage_alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_code TEXT,
                product_name TEXT NOT NULL,
                details TEXT,
                priority TEXT NOT NULL DEFAULT \'alta\',
                status TEXT NOT NULL DEFAULT \'pending\',
                requested_by_user_id INTEGER NOT NULL,
                accepted_by_user_id INTEGER,
                accepted_at TEXT,
                resolved_by_user_id INTEGER,
                resolved_at TEXT,
                closed_by_user_id INTEGER,
                closed_at TEXT,
                resolution_note TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (accepted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (resolved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (closed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS operator_ti_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                requested_by_user_id INTEGER NOT NULL,
                reason TEXT NOT NULL,
                details TEXT,
                status TEXT NOT NULL DEFAULT \'pending\',
                reviewed_by_user_id INTEGER,
                reviewed_at TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT,
                FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
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
        self::ensureAssetColumn($pdo, 'ownership_type', 'TEXT');
        self::ensureAssetColumn($pdo, 'department_id', 'INTEGER');
        self::ensureAssetColumn($pdo, 'network_mode', 'TEXT');
        self::ensureAssetColumn($pdo, 'ip_address', 'TEXT');
        self::ensureAssetColumn($pdo, 'asset_name', 'TEXT');
        self::ensureAssetColumn($pdo, 'brand_name', 'TEXT');
        self::ensureAssetColumn($pdo, 'model_name', 'TEXT');
        self::ensureUserColumn($pdo, 'role', 'TEXT NOT NULL DEFAULT \'admin\'');
        self::ensureUserColumn($pdo, 'department_id', 'INTEGER');
        self::ensureUserColumn($pdo, 'staff_id', 'INTEGER');
        self::ensureUserColumn($pdo, 'is_seller', 'INTEGER NOT NULL DEFAULT 0');
        self::ensureCommercialBoardColumn($pdo, 'is_archived', 'INTEGER NOT NULL DEFAULT 0');
        self::ensureCommercialBoardColumn($pdo, 'archived_at', 'TEXT');
        self::ensureCommercialBoardColumn($pdo, 'stage_name_todo', 'TEXT');
        self::ensureCommercialBoardColumn($pdo, 'stage_name_doing', 'TEXT');
        self::ensureCommercialBoardColumn($pdo, 'stage_name_review', 'TEXT');
        self::ensureCommercialBoardColumn($pdo, 'stage_name_done', 'TEXT');
        self::ensureCommercialTaskColumn($pdo, 'customer_name', 'TEXT');
        self::ensureCommercialTaskColumn($pdo, 'company_name', 'TEXT');
        self::ensureCommercialTaskColumn($pdo, 'deal_value', 'REAL');
        self::ensureCommercialTaskColumn($pdo, 'tag_name', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'owner_user_id', 'INTEGER');
        self::ensureCrmClientColumn($pdo, 'erp_customer_code', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'seller_code', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'client_name', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'company_name', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'phone', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'whatsapp', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'neighborhood', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'birth_date', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'email', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'status', 'TEXT NOT NULL DEFAULT \'ativo\'');
        self::ensureCrmClientColumn($pdo, 'notes', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'last_purchase_date', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'total_spent', 'REAL NOT NULL DEFAULT 0');
        self::ensureCrmClientColumn($pdo, 'purchase_count', 'INTEGER NOT NULL DEFAULT 0');
        self::ensureCrmClientColumn($pdo, 'ticket_avg', 'REAL NOT NULL DEFAULT 0');
        self::ensureCrmClientColumn($pdo, 'days_without_purchase', 'INTEGER');
        self::ensureCrmClientColumn($pdo, 'status_customer', 'TEXT NOT NULL DEFAULT \'NOVO\'');
        self::ensureCrmClientColumn($pdo, 'crm_kanban_stage', 'TEXT');
        self::ensureCrmClientColumn($pdo, 'updated_at', 'TEXT');
        self::ensureCrmSalesColumn($pdo, 'order_number', 'TEXT');
        self::ensureCrmSalesColumn($pdo, 'invoice_number', 'TEXT');
        self::ensureCrmSalesColumn($pdo, 'payment_method', 'TEXT');
        self::ensureCrmSalesColumn($pdo, 'products_text', 'TEXT');
        self::ensureCrmSettingsColumn($pdo, 'vip_amount_threshold', 'REAL NOT NULL DEFAULT 1000');
        self::ensureCrmSettingsColumn($pdo, 'inactive_after_days', 'INTEGER NOT NULL DEFAULT 60');
        self::ensureCrmSettingsColumn($pdo, 'active_after_days', 'INTEGER NOT NULL DEFAULT 30');
        self::ensureCrmSettingsColumn($pdo, 'new_after_days', 'INTEGER NOT NULL DEFAULT 30');
        self::ensureCrmSettingsColumn($pdo, 'recurrence_window_days', 'INTEGER NOT NULL DEFAULT 90');
        self::ensureCrmSettingsColumn($pdo, 'recurrence_min_purchases', 'INTEGER NOT NULL DEFAULT 3');
        self::ensureCrmSettingsColumn($pdo, 'auto_status_enabled', 'INTEGER NOT NULL DEFAULT 1');
        self::ensureShortageColumn($pdo, 'product_code', 'TEXT');
        self::ensureShortageColumn($pdo, 'resolved_by_user_id', 'INTEGER');
        self::ensureShortageColumn($pdo, 'resolved_at', 'TEXT');
        self::ensureShortageColumn($pdo, 'closed_by_user_id', 'INTEGER');
        self::ensureShortageColumn($pdo, 'closed_at', 'TEXT');
        self::ensureShortageColumn($pdo, 'resolution_note', 'TEXT');

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_type ON assets(type)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status ON assets(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_staff ON assets(staff_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_category ON assets(category_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_contract ON assets(contract_type_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status_id ON assets(status_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_warranty_until ON assets(warranty_until)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_contract_until ON assets(contract_until)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_ownership_type ON assets(ownership_type)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_department_id ON assets(department_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_network_mode ON assets(network_mode)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_ip_address ON assets(ip_address)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_asset_name ON assets(asset_name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_brand_name ON assets(brand_name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_model_name ON assets(model_name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_movements_asset_date ON asset_movements(asset_id, created_at DESC)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_home_req_asset ON home_equipment_requests(asset_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_home_req_status ON home_equipment_requests(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_home_req_requester ON home_equipment_requests(requester_staff_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_board_owner ON commercial_boards(owner_user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_board_archived ON commercial_boards(is_archived)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_member_user ON commercial_board_members(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_task_board ON commercial_tasks(board_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_task_status ON commercial_tasks(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_task_assignee ON commercial_tasks(assignee_user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_task_due_date ON commercial_tasks(due_date)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_task_customer ON commercial_tasks(customer_name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_comment_task ON commercial_task_comments(task_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_com_comment_user ON commercial_task_comments(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_owner ON crm_clients(owner_user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_erp_code ON crm_clients(erp_customer_code)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_seller_code ON crm_clients(seller_code)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_phone ON crm_clients(phone)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_name ON crm_clients(client_name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_status ON crm_clients(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_status_customer ON crm_clients(status_customer)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_kanban_stage ON crm_clients(crm_kanban_stage)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_last_purchase ON crm_clients(last_purchase_date)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_neighborhood ON crm_clients(neighborhood)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_sale_client ON crm_sales(client_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_sale_seller ON crm_sales(seller_user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_sale_date ON crm_sales(sale_date)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_sale_order_number ON crm_sales(order_number)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_sale_invoice_number ON crm_sales(invoice_number)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_contact_client ON crm_contact_history(client_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_contact_date ON crm_contact_history(contact_date)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_crm_client_tag_tag ON crm_client_tags(tag_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_purchase_product_name ON purchase_products(name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_purchase_alert_status ON purchase_shortage_alerts(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_purchase_alert_user ON purchase_shortage_alerts(requested_by_user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_purchase_alert_code ON purchase_shortage_alerts(product_code)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_purchase_alert_created ON purchase_shortage_alerts(created_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ti_req_status ON operator_ti_requests(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ti_req_user ON operator_ti_requests(requested_by_user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_department ON users(department_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_staff ON users(staff_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_is_seller ON users(is_seller)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_perm_user ON user_route_permissions(user_id)');

        self::seedDefaults($pdo);

        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, name, role, department_id, staff_id, is_seller)
                 VALUES (:username, :password_hash, :name, :role, :department_id, :staff_id, :is_seller)'
            );
            $stmt->execute([
                ':username' => $config['default_admin']['username'],
                ':password_hash' => password_hash($config['default_admin']['password'], PASSWORD_DEFAULT),
                ':name' => $config['default_admin']['name'],
                ':role' => 'admin',
                ':department_id' => null,
                ':staff_id' => null,
                ':is_seller' => 0,
            ]);
        }

        $pdo->exec(
            'INSERT OR IGNORE INTO crm_settings (
                id, followup_after_days, vip_amount_threshold, inactive_after_days, active_after_days,
                new_after_days, recurrence_window_days, recurrence_min_purchases, updated_at
             ) VALUES (
                1, 30, 1000, 60, 30, 30, 90, 3, CURRENT_TIMESTAMP
             )'
        );
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

    private static function ensureShortageColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(purchase_shortage_alerts)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }

        $pdo->exec(sprintf('ALTER TABLE purchase_shortage_alerts ADD COLUMN %s %s', $column, $definition));
    }

    private static function ensureCommercialBoardColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(commercial_boards)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }

        $pdo->exec(sprintf('ALTER TABLE commercial_boards ADD COLUMN %s %s', $column, $definition));
    }

    private static function ensureCommercialTaskColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(commercial_tasks)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }

        $pdo->exec(sprintf('ALTER TABLE commercial_tasks ADD COLUMN %s %s', $column, $definition));
    }

    private static function ensureCrmClientColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(crm_clients)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }

        $pdo->exec(sprintf('ALTER TABLE crm_clients ADD COLUMN %s %s', $column, $definition));
    }

    private static function ensureCrmSalesColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(crm_sales)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }
        $pdo->exec(sprintf('ALTER TABLE crm_sales ADD COLUMN %s %s', $column, $definition));
    }

    private static function ensureCrmSettingsColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->query('PRAGMA table_info(crm_settings)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $item) {
            if (($item['name'] ?? null) === $column) {
                return;
            }
        }
        $pdo->exec(sprintf('ALTER TABLE crm_settings ADD COLUMN %s %s', $column, $definition));
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
