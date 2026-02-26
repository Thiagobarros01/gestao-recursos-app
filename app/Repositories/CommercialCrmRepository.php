<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\AccessControl;
use PDO;
use Throwable;

final class CommercialCrmRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function settings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT followup_after_days, vip_amount_threshold, inactive_after_days, active_after_days,
                    new_after_days, recurrence_window_days, recurrence_min_purchases, auto_status_enabled
             FROM crm_settings
             WHERE id = 1
             LIMIT 1'
        );
        $row = $stmt->fetch();

        return [
            'followup_after_days' => (int) ($row['followup_after_days'] ?? 30),
            'vip_amount_threshold' => (float) ($row['vip_amount_threshold'] ?? 1000),
            'inactive_after_days' => (int) ($row['inactive_after_days'] ?? 60),
            'active_after_days' => (int) ($row['active_after_days'] ?? 30),
            'new_after_days' => (int) ($row['new_after_days'] ?? 30),
            'recurrence_window_days' => (int) ($row['recurrence_window_days'] ?? 90),
            'recurrence_min_purchases' => (int) ($row['recurrence_min_purchases'] ?? 3),
            'auto_status_enabled' => (int) ($row['auto_status_enabled'] ?? 1),
        ];
    }

    public function updateSettings(array $data, int $updatedByUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crm_settings (
                id, followup_after_days, vip_amount_threshold, inactive_after_days, active_after_days,
                new_after_days, recurrence_window_days, recurrence_min_purchases, auto_status_enabled, updated_by_user_id, updated_at
             ) VALUES (
                1, :followup_after_days, :vip_amount_threshold, :inactive_after_days, :active_after_days,
                :new_after_days, :recurrence_window_days, :recurrence_min_purchases, :auto_status_enabled, :updated_by_user_id, CURRENT_TIMESTAMP
             )
             ON CONFLICT(id) DO UPDATE SET
                followup_after_days = excluded.followup_after_days,
                vip_amount_threshold = excluded.vip_amount_threshold,
                inactive_after_days = excluded.inactive_after_days,
                active_after_days = excluded.active_after_days,
                new_after_days = excluded.new_after_days,
                recurrence_window_days = excluded.recurrence_window_days,
                recurrence_min_purchases = excluded.recurrence_min_purchases,
                auto_status_enabled = excluded.auto_status_enabled,
                updated_by_user_id = excluded.updated_by_user_id,
                updated_at = CURRENT_TIMESTAMP'
        );

        return $stmt->execute([
            ':followup_after_days' => max(1, (int) ($data['followup_after_days'] ?? 30)),
            ':vip_amount_threshold' => max(0, (float) ($data['vip_amount_threshold'] ?? 1000)),
            ':inactive_after_days' => max(1, (int) ($data['inactive_after_days'] ?? 60)),
            ':active_after_days' => max(1, (int) ($data['active_after_days'] ?? 30)),
            ':new_after_days' => max(1, (int) ($data['new_after_days'] ?? 30)),
            ':recurrence_window_days' => max(1, (int) ($data['recurrence_window_days'] ?? 90)),
            ':recurrence_min_purchases' => max(1, (int) ($data['recurrence_min_purchases'] ?? 3)),
            ':auto_status_enabled' => !empty($data['auto_status_enabled']) ? 1 : 0,
            ':updated_by_user_id' => $updatedByUserId,
        ]);
    }

    public function refreshClientSummaries(array $settings, ?array $user): void
    {
        if (!$user) {
            return;
        }

        $autoStatusEnabled = !empty($settings['auto_status_enabled']);
        $statusExpr = $autoStatusEnabled
            ? 'CASE
                    WHEN COALESCE((SELECT SUM(s.amount) FROM crm_sales s WHERE s.client_id = crm_clients.id), 0) >= :vip_amount_threshold
                    THEN \'VIP\'
                    WHEN CAST(julianday(date(\'now\')) - julianday(date(crm_clients.created_at)) AS INTEGER) <= :new_days
                    THEN \'NOVO\'
                    WHEN (SELECT MAX(s.sale_date) FROM crm_sales s WHERE s.client_id = crm_clients.id) IS NOT NULL
                         AND CAST(julianday(date(\'now\')) - julianday((SELECT MAX(s.sale_date) FROM crm_sales s WHERE s.client_id = crm_clients.id)) AS INTEGER) > :inactive_days
                    THEN \'INATIVO\'
                    WHEN (SELECT MAX(s.sale_date) FROM crm_sales s WHERE s.client_id = crm_clients.id) IS NOT NULL
                    THEN \'ATIVO\'
                    ELSE \'INATIVO\'
                 END'
            : 'COALESCE(status_customer, \'NOVO\')';

        $sql =
            'UPDATE crm_clients
             SET total_spent = COALESCE((SELECT SUM(s.amount) FROM crm_sales s WHERE s.client_id = crm_clients.id), 0),
                 purchase_count = COALESCE((SELECT COUNT(*) FROM crm_sales s WHERE s.client_id = crm_clients.id), 0),
                 ticket_avg = CASE
                    WHEN COALESCE((SELECT COUNT(*) FROM crm_sales s WHERE s.client_id = crm_clients.id), 0) > 0
                    THEN COALESCE((SELECT SUM(s.amount) FROM crm_sales s WHERE s.client_id = crm_clients.id), 0)
                         / (SELECT COUNT(*) FROM crm_sales s WHERE s.client_id = crm_clients.id)
                    ELSE 0
                 END,
                 last_purchase_date = (SELECT MAX(s.sale_date) FROM crm_sales s WHERE s.client_id = crm_clients.id),
                 days_without_purchase = CASE
                    WHEN (SELECT MAX(s.sale_date) FROM crm_sales s WHERE s.client_id = crm_clients.id) IS NULL THEN NULL
                    ELSE CAST(julianday(date(\'now\')) - julianday((SELECT MAX(s.sale_date) FROM crm_sales s WHERE s.client_id = crm_clients.id)) AS INTEGER)
                 END,
                 status_customer = ' . $statusExpr . ',
                 updated_at = CURRENT_TIMESTAMP
             WHERE 1=1';

        $params = [
            ':inactive_days' => max(1, (int) ($settings['inactive_after_days'] ?? 60)),
            ':vip_amount_threshold' => max(0, (float) ($settings['vip_amount_threshold'] ?? 1000)),
            ':new_days' => max(1, (int) ($settings['new_after_days'] ?? 30)),
        ];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function clientsForUser(?array $user, array $filters = []): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT c.*,
                    owner.name AS owner_name,
                    (SELECT GROUP_CONCAT(t.name, \', \')
                     FROM crm_client_tags ct
                     INNER JOIN crm_tags t ON t.id = ct.tag_id
                     WHERE ct.client_id = c.id) AS tags_text,
                    (SELECT MAX(ch.contact_date) FROM crm_contact_history ch WHERE ch.client_id = c.id) AS last_contact_date
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             WHERE 1=1';
        $params = [];
        $this->applyClientFilters($sql, $params, $user, $filters, 'c');

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(200, (int) ($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $exactPhone = trim((string) ($filters['main_search'] ?? ''));
        $sql .= ' ORDER BY ';
        if ($exactPhone !== '') {
            $sql .= 'CASE WHEN c.phone = :order_phone_exact THEN 0 ELSE 1 END, ';
        }
        $sql .= 'COALESCE(c.last_purchase_date, \'\') DESC, c.id DESC LIMIT :limit_rows OFFSET :offset_rows';
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        if ($exactPhone !== '') {
            $stmt->bindValue(':order_phone_exact', $exactPhone, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_rows', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset_rows', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function clientsCountForUser(?array $user, array $filters = []): int
    {
        if (!$user) {
            return 0;
        }

        $sql =
            'SELECT COUNT(*)
             FROM crm_clients c
             WHERE 1=1';
        $params = [];
        $this->applyClientFilters($sql, $params, $user, $filters, 'c');

        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function clientsMaster(int $limit = 200): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, owner.name AS owner_name
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             ORDER BY c.updated_at DESC, c.id DESC
             LIMIT :limit_rows'
        );
        $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findClientMasterById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM crm_clients WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function erpCustomerCodeExists(string $erpCode, ?int $ignoreId = null): bool
    {
        if ($erpCode === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM crm_clients WHERE erp_customer_code = :erp_customer_code';
        $params = [':erp_customer_code' => strtoupper($erpCode)];
        if ($ignoreId !== null && $ignoreId > 0) {
            $sql .= ' AND id <> :ignore_id';
            $params[':ignore_id'] = $ignoreId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function statusCountsForUser(?array $user): array
    {
        if (!$user) {
            return ['ATIVO' => 0, 'INATIVO' => 0, 'VIP' => 0, 'NOVO' => 0];
        }

        $sql =
            'SELECT status_customer, COUNT(*) AS total
             FROM crm_clients
             WHERE 1=1';
        $params = [];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }

        $sql .= ' GROUP BY status_customer';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $map = ['ATIVO' => 0, 'INATIVO' => 0, 'VIP' => 0, 'NOVO' => 0];
        foreach ($rows as $row) {
            $status = (string) ($row['status_customer'] ?? '');
            if (!isset($map[$status])) {
                continue;
            }
            $map[$status] = (int) ($row['total'] ?? 0);
        }
        return $map;
    }

    public function clientOptionsForUser(?array $user): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT id, erp_customer_code, client_name, company_name
             FROM crm_clients
             WHERE status <> \'inativo\'';
        $params = [];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }

        $sql .= ' ORDER BY client_name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function tags(): array
    {
        return $this->pdo->query(
            'SELECT id, name
             FROM crm_tags
             ORDER BY name ASC'
        )->fetchAll();
    }

    public function phoneExists(string $phone): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM crm_clients
             WHERE phone = :phone'
        );
        $stmt->execute([':phone' => $phone]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findClientByPhoneForUser(string $phone, ?array $user): ?array
    {
        if (!$user || $phone === '') {
            return null;
        }

        $sql = 'SELECT id, client_name, phone FROM crm_clients WHERE phone = :phone';
        $params = [':phone' => $phone];
        if (!$this->canSeeAll($user)) {
            $sql .= ' AND owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findClientByErpCodeForUser(string $erpCode, ?array $user): ?array
    {
        if (!$user || $erpCode === '') {
            return null;
        }

        $sql = 'SELECT id, client_name, phone, erp_customer_code FROM crm_clients WHERE erp_customer_code = :erp_customer_code';
        $params = [':erp_customer_code' => $erpCode];
        if (!$this->canSeeAll($user)) {
            $sql .= ' AND owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateClientQuickById(int $clientId, array $data): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE crm_clients
             SET client_name = :client_name,
                 erp_customer_code = :erp_customer_code,
                 email = :email,
                 birth_date = :birth_date,
                 notes = :notes,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $clientId,
            ':client_name' => trim((string) ($data['client_name'] ?? '')),
            ':erp_customer_code' => ($data['erp_customer_code'] ?? '') !== '' ? strtoupper(trim((string) $data['erp_customer_code'])) : null,
            ':email' => ($data['email'] ?? '') !== '' ? trim((string) $data['email']) : null,
            ':birth_date' => ($data['birth_date'] ?? '') !== '' ? trim((string) $data['birth_date']) : null,
            ':notes' => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
        ]);
    }

    public function createClient(array $data): int
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO crm_clients (
                    owner_user_id, erp_customer_code, seller_code, client_name, company_name, phone, whatsapp,
                    neighborhood, birth_date, email, status, notes, updated_at
                 ) VALUES (
                    :owner_user_id, :erp_customer_code, :seller_code, :client_name, :company_name, :phone, :whatsapp,
                    :neighborhood, :birth_date, :email, :status, :notes, CURRENT_TIMESTAMP
                 )'
            );
            $ok = $stmt->execute([
                ':owner_user_id' => (int) $data['owner_user_id'],
                ':erp_customer_code' => ($data['erp_customer_code'] ?? '') !== '' ? (string) $data['erp_customer_code'] : null,
                ':seller_code' => ($data['seller_code'] ?? '') !== '' ? (string) $data['seller_code'] : null,
                ':client_name' => (string) $data['client_name'],
                ':company_name' => ($data['company_name'] ?? '') !== '' ? (string) $data['company_name'] : null,
                ':phone' => (string) $data['phone'],
                ':whatsapp' => ($data['whatsapp'] ?? '') !== '' ? (string) $data['whatsapp'] : null,
                ':neighborhood' => ($data['neighborhood'] ?? '') !== '' ? (string) $data['neighborhood'] : null,
                ':birth_date' => ($data['birth_date'] ?? '') !== '' ? (string) $data['birth_date'] : null,
                ':email' => ($data['email'] ?? '') !== '' ? (string) $data['email'] : null,
                ':status' => (string) ($data['status'] ?? 'ativo'),
                ':notes' => ($data['notes'] ?? '') !== '' ? (string) $data['notes'] : null,
            ]);
            if (!$ok) {
                $this->pdo->rollBack();
                return 0;
            }

            $clientId = (int) $this->pdo->lastInsertId();
            $this->syncClientTags($clientId, (string) ($data['tags_text'] ?? ''));
            $this->ensureLoyaltyRow($clientId);
            $this->pdo->commit();
            return $clientId;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return 0;
        }
    }

    public function createSale(
        int $clientId,
        int $sellerUserId,
        string $saleDate,
        float $amount,
        string $notes,
        string $orderNumber,
        string $paymentMethod = '',
        string $invoiceNumber = '',
        string $productsText = ''
    ): bool
    {
        try {
            $this->pdo->beginTransaction();

            $insert = $this->pdo->prepare(
                'INSERT INTO crm_sales (client_id, seller_user_id, order_number, invoice_number, sale_date, amount, payment_method, products_text, notes)
                 VALUES (:client_id, :seller_user_id, :order_number, :invoice_number, :sale_date, :amount, :payment_method, :products_text, :notes)'
            );
            $okInsert = $insert->execute([
                ':client_id' => $clientId,
                ':seller_user_id' => $sellerUserId,
                ':order_number' => $orderNumber !== '' ? $orderNumber : null,
                ':invoice_number' => $invoiceNumber !== '' ? $invoiceNumber : null,
                ':sale_date' => $saleDate,
                ':amount' => max(0, $amount),
                ':payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
                ':products_text' => $productsText !== '' ? $productsText : null,
                ':notes' => $notes !== '' ? $notes : null,
            ]);
            if (!$okInsert) {
                $this->pdo->rollBack();
                return false;
            }

            $this->applySaleToClient($clientId, $saleDate);
            $this->addLoyaltyPoints($clientId, $amount);

            $this->pdo->commit();
            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function createContactHistory(int $clientId, string $type, string $notes, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crm_contact_history (client_id, contact_type, notes, user_id)
             VALUES (:client_id, :contact_type, :notes, :user_id)'
        );
        return $stmt->execute([
            ':client_id' => $clientId,
            ':contact_type' => $type,
            ':notes' => $notes !== '' ? $notes : null,
            ':user_id' => $userId,
        ]);
    }

    public function salesForUser(?array $user, int $limit = 30, int $sellerFilterUserId = 0): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT s.*,
                    c.erp_customer_code,
                    c.client_name,
                    c.company_name,
                    seller.name AS seller_name
             FROM crm_sales s
             INNER JOIN crm_clients c ON c.id = s.client_id
             INNER JOIN users seller ON seller.id = s.seller_user_id
             WHERE 1=1';
        $params = [];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND s.seller_user_id = :seller_user_id';
            $params[':seller_user_id'] = (int) $user['id'];
        } elseif ($sellerFilterUserId > 0) {
            $sql .= ' AND s.seller_user_id = :seller_filter_user_id';
            $params[':seller_filter_user_id'] = $sellerFilterUserId;
        }

        $sql .= ' ORDER BY s.sale_date DESC, s.id DESC LIMIT :limit_rows';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function followupClientsForUser(?array $user, int $afterDays, int $ownerFilterUserId = 0): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT c.id, c.erp_customer_code, c.seller_code, c.client_name, c.phone, c.company_name, c.last_purchase_date, c.owner_user_id,
                    c.days_without_purchase, c.total_spent,
                    owner.name AS owner_name
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             WHERE c.last_purchase_date IS NOT NULL
               AND c.last_purchase_date <> \'\'
               AND c.days_without_purchase >= :after_days';
        $params = [':after_days' => max(1, $afterDays)];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND c.owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        } elseif ($ownerFilterUserId > 0) {
            $sql .= ' AND c.owner_user_id = :owner_filter_user_id';
            $params[':owner_filter_user_id'] = $ownerFilterUserId;
        }

        $sql .= ' ORDER BY c.days_without_purchase DESC, c.client_name ASC LIMIT 100';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function canUseClient(int $clientId, ?array $user): bool
    {
        if (!$user || $clientId <= 0) {
            return false;
        }
        if ($this->canSeeAll($user)) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM crm_clients
             WHERE id = :id
               AND owner_user_id = :owner_user_id'
        );
        $stmt->execute([
            ':id' => $clientId,
            ':owner_user_id' => (int) $user['id'],
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findClientByIdForUser(int $clientId, ?array $user): ?array
    {
        if (!$user || $clientId <= 0) {
            return null;
        }

        $sql =
            'SELECT c.*,
                    owner.name AS owner_name,
                    (SELECT GROUP_CONCAT(t.name, \', \')
                     FROM crm_client_tags ct
                     INNER JOIN crm_tags t ON t.id = ct.tag_id
                     WHERE ct.client_id = c.id) AS tags_text
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             WHERE c.id = :id';
        $params = [':id' => $clientId];
        if (!$this->canSeeAll($user)) {
            $sql .= ' AND c.owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function crmKanbanBoardForUser(?array $user): array
    {
        if (!$user) {
            return [
                'pipeline' => [
                    'EM_NEGOCIACAO' => [],
                    'COMPRA_NAO_REALIZADA' => [],
                    'COMPRA_REALIZADA' => [],
                    'SEM_ETAPA' => [],
                ],
                'reactivation' => [
                    'D30' => [],
                    'D60' => [],
                    'D90' => [],
                ],
            ];
        }

        $sql =
            'SELECT c.id, c.client_name, c.phone, c.erp_customer_code, c.total_spent, c.purchase_count,
                    c.last_purchase_date, c.days_without_purchase, c.status_customer, c.crm_kanban_stage,
                    owner.name AS owner_name
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             WHERE 1=1';
        $params = [];
        if (!$this->canSeeAll($user)) {
            $sql .= ' AND c.owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }
        $sql .= ' ORDER BY COALESCE(c.last_purchase_date, \'\') DESC, c.id DESC LIMIT 500';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $pipeline = [
            'EM_NEGOCIACAO' => [],
            'COMPRA_NAO_REALIZADA' => [],
            'COMPRA_REALIZADA' => [],
            'SEM_ETAPA' => [],
        ];
        $reactivation = [
            'D30' => [],
            'D60' => [],
            'D90' => [],
        ];

        foreach ($rows as $row) {
            $stage = strtoupper(trim((string) ($row['crm_kanban_stage'] ?? '')));
            if (!isset($pipeline[$stage])) {
                $stage = 'SEM_ETAPA';
            }
            $pipeline[$stage][] = $row;

            $days = $row['days_without_purchase'];
            if ($days === null) {
                continue;
            }
            $days = (int) $days;
            if ($days >= 90) {
                $reactivation['D90'][] = $row;
            } elseif ($days >= 60) {
                $reactivation['D60'][] = $row;
            } elseif ($days >= 30) {
                $reactivation['D30'][] = $row;
            }
        }

        return [
            'pipeline' => $pipeline,
            'reactivation' => $reactivation,
        ];
    }

    public function updateCrmKanbanStage(int $clientId, string $stage, ?array $user): bool
    {
        if ($clientId <= 0 || !$user) {
            return false;
        }
        $allowed = ['EM_NEGOCIACAO', 'COMPRA_NAO_REALIZADA', 'COMPRA_REALIZADA', ''];
        if (!in_array($stage, $allowed, true)) {
            return false;
        }
        if (!$this->canUseClient($clientId, $user)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE crm_clients
             SET crm_kanban_stage = :crm_kanban_stage,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $clientId,
            ':crm_kanban_stage' => $stage !== '' ? $stage : null,
        ]);
    }

    public function salesByClientId(int $clientId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, seller.name AS seller_name
             FROM crm_sales s
             INNER JOIN users seller ON seller.id = s.seller_user_id
             WHERE s.client_id = :client_id
             ORDER BY s.sale_date DESC, s.id DESC
             LIMIT :limit_rows'
        );
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contactHistoryByClientId(int $clientId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.*, u.name AS user_name
             FROM crm_contact_history h
             INNER JOIN users u ON u.id = h.user_id
             WHERE h.client_id = :client_id
             ORDER BY h.contact_date DESC, h.id DESC
             LIMIT :limit_rows'
        );
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function loyaltyByClientId(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT points, updated_at
             FROM crm_loyalty
             WHERE client_id = :client_id
             LIMIT 1'
        );
        $stmt->execute([':client_id' => $clientId]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['points' => 0, 'updated_at' => null];
        }
        return [
            'points' => (int) ($row['points'] ?? 0),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function applySaleToClient(int $clientId, string $saleDate): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE crm_clients
             SET last_purchase_date = CASE
                 WHEN last_purchase_date IS NULL OR last_purchase_date = \'\' OR last_purchase_date < :sale_date THEN :sale_date
                 ELSE last_purchase_date
             END,
             updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $clientId,
            ':sale_date' => $saleDate,
        ]);
    }

    private function addLoyaltyPoints(int $clientId, float $amount): void
    {
        $this->ensureLoyaltyRow($clientId);
        $points = (int) floor(max(0, $amount));
        $stmt = $this->pdo->prepare(
            'UPDATE crm_loyalty
             SET points = points + :points,
                 updated_at = CURRENT_TIMESTAMP
             WHERE client_id = :client_id'
        );
        $stmt->execute([
            ':points' => $points,
            ':client_id' => $clientId,
        ]);
    }

    private function ensureLoyaltyRow(int $clientId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT OR IGNORE INTO crm_loyalty (client_id, points, updated_at)
             VALUES (:client_id, 0, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([':client_id' => $clientId]);
    }

    private function syncClientTags(int $clientId, string $tagsText): void
    {
        $delete = $this->pdo->prepare('DELETE FROM crm_client_tags WHERE client_id = :client_id');
        $delete->execute([':client_id' => $clientId]);

        $tags = array_values(array_unique(array_filter(array_map(
            static fn(string $item): string => strtolower(trim($item)),
            preg_split('/[,;]+/', $tagsText) ?: []
        ), static fn(string $item): bool => $item !== '')));

        if (empty($tags)) {
            return;
        }

        $insertTag = $this->pdo->prepare('INSERT OR IGNORE INTO crm_tags (name) VALUES (:name)');
        $findTag = $this->pdo->prepare('SELECT id FROM crm_tags WHERE name = :name LIMIT 1');
        $insertPivot = $this->pdo->prepare(
            'INSERT OR IGNORE INTO crm_client_tags (client_id, tag_id)
             VALUES (:client_id, :tag_id)'
        );

        foreach ($tags as $tagName) {
            $insertTag->execute([':name' => $tagName]);
            $findTag->execute([':name' => $tagName]);
            $tagId = (int) $findTag->fetchColumn();
            if ($tagId <= 0) {
                continue;
            }
            $insertPivot->execute([
                ':client_id' => $clientId,
                ':tag_id' => $tagId,
            ]);
        }
    }

    private function canSeeAll(array $user): bool
    {
        if (AccessControl::isFullAccess($user)) {
            return true;
        }
        return AccessControl::normalizeRole($user['role'] ?? null) === 'gestor';
    }

    private function applyClientFilters(string &$sql, array &$params, array $user, array $filters, string $alias = 'c'): void
    {
        if (!$this->canSeeAll($user)) {
            $sql .= " AND {$alias}.owner_user_id = :owner_user_id";
            $params[':owner_user_id'] = (int) $user['id'];
        } else {
            $ownerFilter = (int) ($filters['owner_user_id'] ?? 0);
            if ($ownerFilter > 0) {
                $sql .= " AND {$alias}.owner_user_id = :owner_filter";
                $params[':owner_filter'] = $ownerFilter;
            }
        }

        $statusCustomer = trim((string) ($filters['status_customer'] ?? ''));
        if ($statusCustomer !== '') {
            $sql .= " AND {$alias}.status_customer = :status_customer";
            $params[':status_customer'] = $statusCustomer;
        }

        $neighborhood = trim((string) ($filters['neighborhood'] ?? ''));
        if ($neighborhood !== '') {
            $sql .= " AND COALESCE({$alias}.neighborhood, '') LIKE :neighborhood";
            $params[':neighborhood'] = '%' . $neighborhood . '%';
        }

        $mainSearch = trim((string) ($filters['main_search'] ?? ''));
        if ($mainSearch !== '') {
            $sql .= " AND (COALESCE({$alias}.phone, '') = :phone_exact OR {$alias}.client_name LIKE :main_search_name)";
            $params[':phone_exact'] = $mainSearch;
            $params[':main_search_name'] = '%' . $mainSearch . '%';
        } else {
            $clientCode = trim((string) ($filters['client_code'] ?? ''));
            if ($clientCode !== '') {
                $sql .= " AND (COALESCE({$alias}.erp_customer_code, '') LIKE :client_code OR COALESCE({$alias}.phone, '') LIKE :client_code)";
                $params[':client_code'] = '%' . $clientCode . '%';
            }

            $clientName = trim((string) ($filters['client_name'] ?? ''));
            if ($clientName !== '') {
                $sql .= " AND {$alias}.client_name LIKE :client_name";
                $params[':client_name'] = '%' . $clientName . '%';
            }
        }

        $minTotal = trim((string) ($filters['min_total_spent'] ?? ''));
        if ($minTotal !== '' && is_numeric(str_replace(',', '.', $minTotal))) {
            $sql .= " AND {$alias}.total_spent >= :min_total_spent";
            $params[':min_total_spent'] = (float) str_replace(',', '.', $minTotal);
        }

        $maxTotal = trim((string) ($filters['max_total_spent'] ?? ''));
        if ($maxTotal !== '' && is_numeric(str_replace(',', '.', $maxTotal))) {
            $sql .= " AND {$alias}.total_spent <= :max_total_spent";
            $params[':max_total_spent'] = (float) str_replace(',', '.', $maxTotal);
        }

        $lastFrom = trim((string) ($filters['last_purchase_from'] ?? ''));
        if ($lastFrom !== '') {
            $sql .= " AND {$alias}.last_purchase_date >= :last_purchase_from";
            $params[':last_purchase_from'] = $lastFrom;
        }

        $lastTo = trim((string) ($filters['last_purchase_to'] ?? ''));
        if ($lastTo !== '') {
            $sql .= " AND {$alias}.last_purchase_date <= :last_purchase_to";
            $params[':last_purchase_to'] = $lastTo;
        }

        $tagId = (int) ($filters['tag_id'] ?? 0);
        if ($tagId > 0) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM crm_client_tags ct
                WHERE ct.client_id = {$alias}.id AND ct.tag_id = :tag_id
            )";
            $params[':tag_id'] = $tagId;
        }
    }

    private function bindParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }
            $stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
        }
    }
}
