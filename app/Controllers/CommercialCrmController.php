<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\View;
use App\Repositories\CommercialCrmRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class CommercialCrmController
{
    public function __construct(
        private CommercialCrmRepository $crm,
        private UserRepository $users
    ) {
    }

    public function index(): void
    {
        $user = Auth::user();
        $filters = $this->readClientFilters($_GET);
        $settings = $this->crm->settings();
        $this->crm->refreshClientSummaries($settings, $user);
        $followupDays = (int) ($settings['followup_after_days'] ?? 30);
        $ownerFilterUserId = (int) ($filters['owner_user_id'] ?? 0);
        $perPage = 25;
        $totalClients = $this->crm->clientsCountForUser($user, $filters);
        $totalPages = max(1, (int) ceil($totalClients / $perPage));
        $currentPage = min(max(1, (int) ($filters['page'] ?? 1)), $totalPages);
        $filters['page'] = $currentPage;
        $filters['per_page'] = $perPage;

        View::render('commercial/crm', [
            'title' => 'CRM Comercial',
            'currentRoute' => 'commercial.crm',
            'clients' => $this->crm->clientsForUser($user, $filters),
            'clientPagination' => [
                'page' => $currentPage,
                'per_page' => $perPage,
                'total_items' => $totalClients,
                'total_pages' => $totalPages,
            ],
            'statusCounts' => $this->crm->statusCountsForUser($user),
            'clientOptions' => $this->crm->clientOptionsForUser($user),
            'sales' => $this->crm->salesForUser($user, 30, $ownerFilterUserId),
            'followupClients' => $this->crm->followupClientsForUser($user, $followupDays, $ownerFilterUserId),
            'tags' => $this->crm->tags(),
            'users' => $this->users->all(),
            'settings' => $settings,
            'filters' => $filters,
            'canManageSettings' => $this->canManageSettings($user),
            'canSeeAll' => $this->canSeeAll($user),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function clientDetail(): void
    {
        $user = Auth::user();
        $clientId = (int) ($_GET['id'] ?? 0);
        if ($clientId <= 0) {
            View::redirect('commercial.crm&error=1');
        }

        $settings = $this->crm->settings();
        $this->crm->refreshClientSummaries($settings, $user);
        $client = $this->crm->findClientByIdForUser($clientId, $user);
        if ($client === null) {
            View::redirect('commercial.crm&error=3');
        }

        $tab = trim((string) ($_GET['tab'] ?? 'compras'));
        if (!in_array($tab, ['compras', 'observacoes'], true)) {
            $tab = 'compras';
        }

        View::render('commercial/crm_client', [
            'title' => 'Cliente CRM',
            'currentRoute' => 'commercial.crm',
            'client' => $client,
            'sales' => $this->crm->salesByClientId($clientId),
            'contacts' => $this->crm->contactHistoryByClientId($clientId),
            'loyalty' => $this->crm->loyaltyByClientId($clientId),
            'activeTab' => $tab,
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function kanban(): void
    {
        $user = Auth::user();
        $settings = $this->crm->settings();
        $this->crm->refreshClientSummaries($settings, $user);

        View::render('commercial/crm_kanban', [
            'title' => 'CRM Kanban',
            'currentRoute' => 'commercial.crm.kanban',
            'board' => $this->crm->crmKanbanBoardForUser($user),
            'success' => $_GET['ok'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function updateKanbanStage(array $input): void
    {
        $user = Auth::user();
        $clientId = (int) ($input['client_id'] ?? 0);
        $stage = strtoupper(trim((string) ($input['stage'] ?? '')));
        if ($stage === 'SEM_ETAPA') {
            $stage = '';
        }

        if ($clientId <= 0) {
            View::redirect('commercial.crm.kanban&error=1');
        }

        try {
            $ok = $this->crm->updateCrmKanbanStage($clientId, $stage, $user);
            if (!$ok) {
                View::redirect('commercial.crm.kanban&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm.kanban&error=2');
        }

        View::redirect('commercial.crm.kanban&ok=1');
    }

    public function storeClient(array $input): void
    {
        $user = Auth::user();
        $clientName = trim((string) ($input['client_name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        if ($clientName === '' || $phone === '') {
            View::redirect('commercial.crm&error=1');
        }
        if ($this->crm->phoneExists($phone)) {
            $existing = $this->crm->findClientByPhoneForUser($phone, $user);
            if ($existing !== null) {
                View::redirect('commercial.crm&error=4&existing_client_id=' . (int) ($existing['id'] ?? 0));
            }
            View::redirect('commercial.crm&error=4');
        }

        $ownerUserId = (int) ($input['owner_user_id'] ?? 0);
        if ($ownerUserId <= 0 || !$this->canSeeAll($user)) {
            $ownerUserId = (int) ($user['id'] ?? 0);
        }
        $status = trim((string) ($input['status'] ?? 'ativo'));
        if (!in_array($status, ['ativo', 'prospect', 'inativo'], true)) {
            $status = 'ativo';
        }

        try {
            $ok = $this->crm->createClient([
                'owner_user_id' => $ownerUserId,
                'erp_customer_code' => strtoupper(trim((string) ($input['erp_customer_code'] ?? ''))),
                'seller_code' => strtoupper(trim((string) ($input['seller_code'] ?? ''))),
                'client_name' => $clientName,
                'company_name' => trim((string) ($input['company_name'] ?? '')),
                'phone' => $phone,
                'whatsapp' => trim((string) ($input['whatsapp'] ?? '')),
                'neighborhood' => trim((string) ($input['neighborhood'] ?? '')),
                'birth_date' => trim((string) ($input['birth_date'] ?? '')),
                'email' => trim((string) ($input['email'] ?? '')),
                'status' => $status,
                'notes' => trim((string) ($input['notes'] ?? '')),
                'tags_text' => trim((string) ($input['tags_text'] ?? '')),
            ]);
            if ($ok <= 0) {
                View::redirect('commercial.crm&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm&error=2');
        }

        View::redirect('commercial.crm&ok=1');
    }

    public function storeSale(array $input): void
    {
        $user = Auth::user();
        $clientId = (int) ($input['client_id'] ?? 0);
        $saleDate = trim((string) ($input['sale_date'] ?? ''));
        $amountRaw = trim((string) ($input['amount'] ?? '0'));
        $notes = trim((string) ($input['notes'] ?? ''));
        $orderNumber = trim((string) ($input['order_number'] ?? ''));
        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
        $invoiceNumber = trim((string) ($input['invoice_number'] ?? ''));
        $productsText = trim((string) ($input['products_text'] ?? ''));

        if ($clientId <= 0 || !$this->isValidDate($saleDate) || !$this->isValidMoney($amountRaw)) {
            View::redirect('commercial.crm&error=1');
        }
        if (!$this->crm->canUseClient($clientId, $user)) {
            View::redirect('commercial.crm&error=3');
        }

        $amount = (float) str_replace(',', '.', $amountRaw);
        try {
            $ok = $this->crm->createSale(
                $clientId,
                (int) ($user['id'] ?? 0),
                $saleDate,
                $amount,
                $notes,
                $orderNumber,
                $paymentMethod,
                $invoiceNumber,
                $productsText
            );
            if (!$ok) {
                View::redirect('commercial.crm&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm&error=2');
        }

        $returnClient = (int) ($input['return_client'] ?? 0);
        if ($returnClient > 0) {
            View::redirect('commercial.crm.client&id=' . $returnClient . '&tab=compras&ok=2');
        }
        View::redirect('commercial.crm&ok=2');
    }

    public function storeContact(array $input): void
    {
        $user = Auth::user();
        $clientId = (int) ($input['client_id'] ?? 0);
        $contactType = trim((string) ($input['contact_type'] ?? 'WhatsApp'));
        $notes = trim((string) ($input['notes'] ?? ''));
        if ($clientId <= 0 || !in_array($contactType, ['Ligacao', 'WhatsApp', 'Presencial', 'Observacao'], true)) {
            View::redirect('commercial.crm&error=1');
        }
        if (!$this->crm->canUseClient($clientId, $user)) {
            View::redirect('commercial.crm&error=3');
        }

        try {
            $ok = $this->crm->createContactHistory($clientId, $contactType, $notes, (int) ($user['id'] ?? 0));
            if (!$ok) {
                View::redirect('commercial.crm&error=2');
            }
        } catch (Throwable) {
            View::redirect('commercial.crm&error=2');
        }

        $returnClient = (int) ($input['return_client'] ?? 0);
        if ($returnClient > 0) {
            View::redirect('commercial.crm.client&id=' . $returnClient . '&tab=observacoes&ok=4');
        }
        View::redirect('commercial.crm&ok=4');
    }

    public function updateSettings(array $input): void
    {
        $user = Auth::user();
        if (!$this->canManageSettings($user)) {
            View::redirect('settings&error=1');
        }

        $followupAfterDays = (int) ($input['followup_after_days'] ?? 30);
        $vipAmountThreshold = (float) str_replace(',', '.', (string) ($input['vip_amount_threshold'] ?? '1000'));
        $inactiveAfterDays = (int) ($input['inactive_after_days'] ?? 60);
        $activeAfterDays = (int) ($input['active_after_days'] ?? 30);
        $newAfterDays = (int) ($input['new_after_days'] ?? 30);
        $recurrenceWindowDays = (int) ($input['recurrence_window_days'] ?? 90);
        $recurrenceMinPurchases = (int) ($input['recurrence_min_purchases'] ?? 3);
        $autoStatusEnabled = (int) ($input['auto_status_enabled'] ?? 0) === 1 ? 1 : 0;

        if (
            $followupAfterDays <= 0 || $followupAfterDays > 3650 ||
            $inactiveAfterDays <= 0 || $inactiveAfterDays > 3650 ||
            $activeAfterDays <= 0 || $activeAfterDays > 3650 ||
            $newAfterDays <= 0 || $newAfterDays > 3650 ||
            $recurrenceWindowDays <= 0 || $recurrenceWindowDays > 3650 ||
            $recurrenceMinPurchases <= 0 || $recurrenceMinPurchases > 3650 ||
            $vipAmountThreshold < 0
        ) {
            View::redirect('settings&error=1');
        }

        try {
            $ok = $this->crm->updateSettings([
                'followup_after_days' => $followupAfterDays,
                'vip_amount_threshold' => $vipAmountThreshold,
                'inactive_after_days' => $inactiveAfterDays,
                'active_after_days' => $activeAfterDays,
                'new_after_days' => $newAfterDays,
                'recurrence_window_days' => $recurrenceWindowDays,
                'recurrence_min_purchases' => $recurrenceMinPurchases,
                'auto_status_enabled' => $autoStatusEnabled,
            ], (int) ($user['id'] ?? 0));
            if (!$ok) {
                View::redirect('settings&error=2');
            }
        } catch (Throwable) {
            View::redirect('settings&error=2');
        }

        View::redirect('settings&ok=4');
    }

    public function importCsv(array $input, array $files): void
    {
        $user = Auth::user();
        if (!$user) {
            View::redirect('commercial.crm&error=3');
        }

        $type = trim((string) ($input['import_type'] ?? ''));
        if (!in_array($type, ['clientes', 'compras'], true)) {
            View::redirect('commercial.crm&error=5');
        }

        try {
            $csv = $this->readCsvRows($files['csv_file'] ?? null);
            $rows = $csv['rows'];
            if (empty($rows)) {
                View::redirect('commercial.crm&error=5');
            }
            $this->validateImportHeaders($type, $csv['headers']);

            $summary = $type === 'clientes'
                ? $this->importClientsFromRows($rows, $user)
                : $this->importSalesFromRows($rows, $user);

            View::redirect(sprintf(
                'commercial.crm&ok=5&import_type=%s&created=%d&updated=%d&sales=%d&skipped=%d&failed=%d',
                urlencode($type),
                (int) ($summary['created'] ?? 0),
                (int) ($summary['updated'] ?? 0),
                (int) ($summary['sales'] ?? 0),
                (int) ($summary['skipped'] ?? 0),
                (int) ($summary['failed'] ?? 0)
            ));
        } catch (RuntimeException) {
            View::redirect('commercial.crm&error=5');
        }
    }

    public function downloadImportTemplate(): void
    {
        $type = trim((string) ($_GET['type'] ?? 'clientes'));
        if (!in_array($type, ['clientes', 'compras'], true)) {
            $type = 'clientes';
        }

        $filename = $type === 'clientes' ? 'modelo_importacao_clientes.csv' : 'modelo_importacao_compras.csv';
        $csv = $type === 'clientes'
            ? "codigo_cliente_erp,nome,telefone,email,data_nascimento,observacoes\nCLI001,Joao Silva,11999998888,joao@email.com,1990-05-10,Cliente frequente de suplemento\nCLI002,Maria Souza,11988887777,maria@email.com,1987-11-22,Prefere contato por WhatsApp\n"
            : "codigo_cliente_erp,telefone,data_compra,valor,forma_pagamento,numero_pedido,nota_fiscal,produtos,observacao\nCLI001,11999998888,2026-02-20,159.90,Pix,PED-1001,NF-5001,\"FRALDA P, LENCO UMEDECIDO\",Compra balcao\nCLI002,11988887777,2026-02-21,89.50,Cartao,PED-1002,NF-5002,\"SUPLEMENTO X\",Compra ERP importada\n";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }

    private function readClientFilters(array $source): array
    {
        $status = trim((string) ($source['status_filter'] ?? ''));
        if (!in_array($status, ['NOVO', 'ATIVO', 'VIP', 'INATIVO'], true)) {
            $status = '';
        }

        return [
            'q' => trim((string) ($source['q'] ?? '')),
            'main_search' => trim((string) ($source['main_search'] ?? '')),
            'client_code' => strtoupper(trim((string) ($source['client_code_filter'] ?? ''))),
            'client_name' => trim((string) ($source['client_name_filter'] ?? '')),
            'status_customer' => $status,
            'neighborhood' => trim((string) ($source['neighborhood_filter'] ?? '')),
            'tag_id' => (int) ($source['tag_id'] ?? 0),
            'min_total_spent' => trim((string) ($source['min_total_spent'] ?? '')),
            'max_total_spent' => trim((string) ($source['max_total_spent'] ?? '')),
            'last_purchase_from' => trim((string) ($source['last_purchase_from'] ?? '')),
            'last_purchase_to' => trim((string) ($source['last_purchase_to'] ?? '')),
            'owner_user_id' => (int) ($source['owner_user_id'] ?? 0),
            'page' => max(1, (int) ($source['page'] ?? 1)),
        ];
    }

    private function importClientsFromRows(array $rows, array $user): array
    {
        $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $canSeeAll = $this->canSeeAll($user);
        $ownerUserId = (int) ($user['id'] ?? 0);

        foreach ($rows as $row) {
            $name = trim((string) ($row['nome'] ?? ''));
            $erpCode = strtoupper(trim((string) ($row['codigo_cliente_erp'] ?? ($row['erp_customer_code'] ?? ''))));
            $phone = $this->normalizePhone((string) ($row['telefone'] ?? ''));
            if ($name === '' || $phone === '') {
                $summary['failed']++;
                continue;
            }

            $existing = $erpCode !== ''
                ? $this->crm->findClientByErpCodeForUser($erpCode, $user)
                : null;
            if ($existing === null) {
                $existing = $this->crm->findClientByPhoneForUser($phone, $user);
            }
            try {
                if ($existing) {
                    $ok = $this->crm->updateClientQuickById((int) $existing['id'], [
                        'client_name' => $name,
                        'erp_customer_code' => $erpCode,
                        'email' => trim((string) ($row['email'] ?? '')),
                        'birth_date' => trim((string) ($row['data_nascimento'] ?? '')),
                        'notes' => trim((string) ($row['observacoes'] ?? '')),
                    ]);
                    if ($ok) {
                        $summary['updated']++;
                    } else {
                        $summary['failed']++;
                    }
                    continue;
                }

                $importOwner = (int) ($row['owner_user_id'] ?? 0);
                $newId = $this->crm->createClient([
                    'owner_user_id' => $canSeeAll && $importOwner > 0 ? $importOwner : $ownerUserId,
                    'erp_customer_code' => $erpCode,
                    'client_name' => $name,
                    'phone' => $phone,
                    'email' => trim((string) ($row['email'] ?? '')),
                    'birth_date' => trim((string) ($row['data_nascimento'] ?? '')),
                    'notes' => trim((string) ($row['observacoes'] ?? '')),
                    'status' => 'ativo',
                ]);
                if ($newId > 0) {
                    $summary['created']++;
                } else {
                    $summary['failed']++;
                }
            } catch (Throwable) {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    private function importSalesFromRows(array $rows, array $user): array
    {
        $summary = ['sales' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $sellerUserId = (int) ($user['id'] ?? 0);

        foreach ($rows as $row) {
            $erpCode = strtoupper(trim((string) ($row['codigo_cliente_erp'] ?? ($row['erp_customer_code'] ?? ''))));
            $phone = $this->normalizePhone((string) ($row['telefone'] ?? ''));
            $saleDate = trim((string) ($row['data_compra'] ?? ''));
            $amountRaw = trim((string) ($row['valor'] ?? ''));
            if (($erpCode === '' && $phone === '') || !$this->isValidDate($saleDate) || !$this->isValidMoney($amountRaw)) {
                $summary['failed']++;
                continue;
            }

            $client = $erpCode !== ''
                ? $this->crm->findClientByErpCodeForUser($erpCode, $user)
                : null;
            if (!$client && $phone !== '') {
                $client = $this->crm->findClientByPhoneForUser($phone, $user);
            }
            if (!$client) {
                $summary['skipped']++;
                continue;
            }

            try {
                $ok = $this->crm->createSale(
                    (int) $client['id'],
                    $sellerUserId,
                    $saleDate,
                    (float) str_replace(',', '.', $amountRaw),
                    trim((string) ($row['observacao'] ?? '')),
                    trim((string) ($row['numero_pedido'] ?? '')),
                    trim((string) ($row['forma_pagamento'] ?? '')),
                    trim((string) ($row['nota_fiscal'] ?? '')),
                    trim((string) ($row['produtos'] ?? ($row['itens'] ?? '')))
                );
                if ($ok) {
                    $summary['sales']++;
                } else {
                    $summary['failed']++;
                }
            } catch (Throwable) {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    private function readCsvRows(mixed $file): array
    {
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('invalid_upload');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new RuntimeException('tmp_missing');
        }

        $handle = fopen($tmpName, 'rb');
        if ($handle === false) {
            throw new RuntimeException('open_failed');
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = $this->detectCsvDelimiter((string) $firstLine);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!is_array($headers) || empty($headers)) {
            fclose($handle);
            throw new RuntimeException('headers_missing');
        }

        $normalizedHeaders = array_map(fn($h) => $this->normalizeHeader((string) $h), $headers);
        $rows = [];

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($line === [null] || $line === []) {
                continue;
            }
            $row = [];
            foreach ($normalizedHeaders as $idx => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim((string) ($line[$idx] ?? ''));
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return [
            'headers' => $normalizedHeaders,
            'rows' => $rows,
        ];
    }

    private function detectCsvDelimiter(string $line): string
    {
        $comma = substr_count($line, ',');
        $semicolon = substr_count($line, ';');
        return $semicolon > $comma ? ';' : ',';
    }

    private function normalizeHeader(string $header): string
    {
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        $header = strtr($header, [
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A',
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'É' => 'E', 'Ê' => 'E', 'é' => 'e', 'ê' => 'e',
            'Í' => 'I', 'í' => 'i',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'Ú' => 'U', 'ú' => 'u',
            'Ç' => 'C', 'ç' => 'c',
        ]);
        $header = strtolower(trim($header));
        $map = [
            'nome' => 'nome',
            'codigo_cliente_erp' => 'codigo_cliente_erp',
            'cod_cliente' => 'codigo_cliente_erp',
            'codigo cliente' => 'codigo_cliente_erp',
            'codigo do cliente' => 'codigo_cliente_erp',
            'cliente_codigo' => 'codigo_cliente_erp',
            'erp_customer_code' => 'erp_customer_code',
            'telefone' => 'telefone',
            'fone' => 'telefone',
            'email' => 'email',
            'data_nascimento' => 'data_nascimento',
            'datanascimento' => 'data_nascimento',
            'data de nascimento' => 'data_nascimento',
            'observacoes' => 'observacoes',
            'observacao' => 'observacao',
            'data_compra' => 'data_compra',
            'datacompra' => 'data_compra',
            'data compra' => 'data_compra',
            'valor' => 'valor',
            'forma_pagamento' => 'forma_pagamento',
            'formapagamento' => 'forma_pagamento',
            'forma de pagamento' => 'forma_pagamento',
            'nota_fiscal' => 'nota_fiscal',
            'nota fiscal' => 'nota_fiscal',
            'numero_pedido' => 'numero_pedido',
            'numeropedido' => 'numero_pedido',
            'numero pedido' => 'numero_pedido',
            'produtos' => 'produtos',
            'itens' => 'itens',
            'owner_user_id' => 'owner_user_id',
        ];
        return $map[$header] ?? $header;
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);
        return is_string($digits) ? trim($digits) : '';
    }

    private function validateImportHeaders(string $type, array $headers): void
    {
        $headers = array_values(array_filter(array_map('strval', $headers)));
        $required = $type === 'clientes'
            ? ['nome']
            : ['data_compra', 'valor'];

        foreach ($required as $column) {
            if (!in_array($column, $headers, true)) {
                throw new RuntimeException('missing_header:' . $column);
            }
        }

        if ($type === 'clientes' && !in_array('telefone', $headers, true) && !in_array('codigo_cliente_erp', $headers, true) && !in_array('erp_customer_code', $headers, true)) {
            throw new RuntimeException('missing_header:telefone_or_codigo_cliente_erp');
        }

        if ($type === 'compras' && !in_array('telefone', $headers, true) && !in_array('codigo_cliente_erp', $headers, true) && !in_array('erp_customer_code', $headers, true)) {
            throw new RuntimeException('missing_header:telefone_or_codigo_cliente_erp');
        }
    }

    private function canManageSettings(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $role = AccessControl::normalizeRole($user['role'] ?? null);
        return $role === 'admin';
    }

    private function canSeeAll(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (AccessControl::isFullAccess($user)) {
            return true;
        }
        return AccessControl::normalizeRole($user['role'] ?? null) === 'gestor';
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    private function isValidMoney(string $value): bool
    {
        $normalized = str_replace(',', '.', $value);
        return is_numeric($normalized) && (float) $normalized >= 0;
    }
}
