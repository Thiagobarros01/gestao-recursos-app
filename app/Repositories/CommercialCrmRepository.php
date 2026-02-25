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
            'SELECT followup_after_days
             FROM crm_settings
             WHERE id = 1
             LIMIT 1'
        );
        $row = $stmt->fetch();
        return [
            'followup_after_days' => (int) ($row['followup_after_days'] ?? 30),
        ];
    }

    public function updateSettings(int $followupAfterDays, int $updatedByUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crm_settings (id, followup_after_days, updated_by_user_id, updated_at)
             VALUES (1, :followup_after_days, :updated_by_user_id, CURRENT_TIMESTAMP)
             ON CONFLICT(id) DO UPDATE SET
                followup_after_days = excluded.followup_after_days,
                updated_by_user_id = excluded.updated_by_user_id,
                updated_at = CURRENT_TIMESTAMP'
        );
        return $stmt->execute([
            ':followup_after_days' => max(1, $followupAfterDays),
            ':updated_by_user_id' => $updatedByUserId,
        ]);
    }

    public function clientsForUser(?array $user, array $filters = []): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT c.*,
                    owner.name AS owner_name,
                    (SELECT COUNT(*) FROM crm_sales s WHERE s.client_id = c.id) AS total_sales,
                    (SELECT COALESCE(SUM(s.amount), 0) FROM crm_sales s WHERE s.client_id = c.id) AS total_amount,
                    CASE
                        WHEN c.last_purchase_date IS NULL OR c.last_purchase_date = \'\' THEN NULL
                        ELSE CAST(julianday(date(\'now\')) - julianday(c.last_purchase_date) AS INTEGER)
                    END AS days_since_last_purchase
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             WHERE 1=1';
        $params = [];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND c.owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        } else {
            $ownerFilter = (int) ($filters['owner_user_id'] ?? 0);
            if ($ownerFilter > 0) {
                $sql .= ' AND c.owner_user_id = :owner_filter';
                $params[':owner_filter'] = $ownerFilter;
            }
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $sql .= ' AND c.status = :status';
            $params[':status'] = $status;
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $sql .= ' AND (
                c.client_name LIKE :q
                OR COALESCE(c.company_name, \'\') LIKE :q
                OR COALESCE(c.phone, \'\') LIKE :q
                OR COALESCE(c.whatsapp, \'\') LIKE :q
                OR COALESCE(c.email, \'\') LIKE :q
            )';
            $params[':q'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY c.updated_at DESC, c.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function clientOptionsForUser(?array $user): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT id, client_name, company_name
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

    public function createClient(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crm_clients (
                owner_user_id, client_name, company_name, phone, whatsapp, email, status, notes, updated_at
             ) VALUES (
                :owner_user_id, :client_name, :company_name, :phone, :whatsapp, :email, :status, :notes, CURRENT_TIMESTAMP
             )'
        );
        return $stmt->execute([
            ':owner_user_id' => (int) $data['owner_user_id'],
            ':client_name' => (string) $data['client_name'],
            ':company_name' => ($data['company_name'] ?? '') !== '' ? (string) $data['company_name'] : null,
            ':phone' => ($data['phone'] ?? '') !== '' ? (string) $data['phone'] : null,
            ':whatsapp' => ($data['whatsapp'] ?? '') !== '' ? (string) $data['whatsapp'] : null,
            ':email' => ($data['email'] ?? '') !== '' ? (string) $data['email'] : null,
            ':status' => (string) ($data['status'] ?? 'ativo'),
            ':notes' => ($data['notes'] ?? '') !== '' ? (string) $data['notes'] : null,
        ]);
    }

    public function createSale(int $clientId, int $sellerUserId, string $saleDate, float $amount, string $notes): bool
    {
        try {
            $this->pdo->beginTransaction();

            $insert = $this->pdo->prepare(
                'INSERT INTO crm_sales (client_id, seller_user_id, sale_date, amount, notes)
                 VALUES (:client_id, :seller_user_id, :sale_date, :amount, :notes)'
            );
            $okInsert = $insert->execute([
                ':client_id' => $clientId,
                ':seller_user_id' => $sellerUserId,
                ':sale_date' => $saleDate,
                ':amount' => max(0, $amount),
                ':notes' => $notes !== '' ? $notes : null,
            ]);
            if (!$okInsert) {
                $this->pdo->rollBack();
                return false;
            }

            $updateClient = $this->pdo->prepare(
                'UPDATE crm_clients
                 SET last_purchase_date = CASE
                    WHEN last_purchase_date IS NULL OR last_purchase_date = \'\' OR last_purchase_date < :sale_date THEN :sale_date
                    ELSE last_purchase_date
                 END,
                 updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $okUpdate = $updateClient->execute([
                ':id' => $clientId,
                ':sale_date' => $saleDate,
            ]);
            if (!$okUpdate) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function salesForUser(?array $user, int $limit = 30): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT s.*,
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
        }

        $sql .= ' ORDER BY s.sale_date DESC, s.id DESC LIMIT :limit_rows';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function followupClientsForUser(?array $user, int $afterDays): array
    {
        if (!$user) {
            return [];
        }

        $sql =
            'SELECT c.id, c.client_name, c.company_name, c.last_purchase_date, c.owner_user_id,
                    owner.name AS owner_name,
                    CAST(julianday(date(\'now\')) - julianday(c.last_purchase_date) AS INTEGER) AS days_since_last_purchase
             FROM crm_clients c
             INNER JOIN users owner ON owner.id = c.owner_user_id
             WHERE c.last_purchase_date IS NOT NULL
               AND c.last_purchase_date <> \'\'
               AND CAST(julianday(date(\'now\')) - julianday(c.last_purchase_date) AS INTEGER) >= :after_days';
        $params = [':after_days' => max(1, $afterDays)];

        if (!$this->canSeeAll($user)) {
            $sql .= ' AND c.owner_user_id = :owner_user_id';
            $params[':owner_user_id'] = (int) $user['id'];
        }

        $sql .= ' ORDER BY days_since_last_purchase DESC, c.client_name ASC LIMIT 60';
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

    private function canSeeAll(array $user): bool
    {
        if (AccessControl::isFullAccess($user)) {
            return true;
        }
        return AccessControl::normalizeRole($user['role'] ?? null) === 'gestor';
    }
}

