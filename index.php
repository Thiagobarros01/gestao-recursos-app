<?php

declare(strict_types=1);

use App\Controllers\AreaController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
use App\Controllers\CommercialKanbanController;
use App\Controllers\ContractController;
use App\Controllers\DashboardController;
use App\Controllers\HomeRequestController;
use App\Controllers\StaffController;
use App\Controllers\TISettingsController;
use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Repositories\CommercialKanbanRepository;
use App\Repositories\HomeRequestRepository;
use App\Repositories\LookupRepository;
use App\Repositories\StaffRepository;
use App\Repositories\UserRepository;
use App\Services\SchemaService;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

$config = require __DIR__ . '/config/app.php';
Auth::init($config['session_name']);

$pdo = Database::getConnection($config);
SchemaService::migrate($pdo, $config);

$userRepo = new UserRepository($pdo);
$staffRepo = new StaffRepository($pdo);
$lookupRepo = new LookupRepository($pdo);
$assetRepo = new AssetRepository($pdo);
$homeRequestRepo = new HomeRequestRepository($pdo);
$commercialKanbanRepo = new CommercialKanbanRepository($pdo);
$homeRequestRepo->autoMarkOverdueAsReturned();

$authController = new AuthController($userRepo);
$areaController = new AreaController();
$dashboardController = new DashboardController($assetRepo, $staffRepo, $lookupRepo, $homeRequestRepo);
$staffController = new StaffController($staffRepo, $lookupRepo);
$assetController = new AssetController($assetRepo, $staffRepo, $lookupRepo);
$contractController = new ContractController($assetRepo, $homeRequestRepo, $lookupRepo);
$commercialKanbanController = new CommercialKanbanController($commercialKanbanRepo, $userRepo);
$tiSettingsController = new TISettingsController($lookupRepo, $userRepo, $staffRepo);
$homeRequestController = new HomeRequestController($homeRequestRepo, $assetRepo, $staffRepo, $lookupRepo);

$route = $_GET['r'] ?? 'areas';
$isGuestRoute = in_array($route, ['login', 'login.submit'], true);

if (!$isGuestRoute && !Auth::check()) {
    View::redirect('login');
}

if ($isGuestRoute && Auth::check()) {
    View::redirect('areas');
}

if (!$isGuestRoute && Auth::check() && !AccessControl::canAccessRoute($route, Auth::user())) {
    http_response_code(403);
    echo 'Acesso negado para esta rota';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($route) {
        case 'login.submit':
            $authController->login($_POST);
            break;
        case 'ti.staff.store':
            $staffController->store($_POST);
            break;
        case 'ti.staff.update':
            $staffController->update($_POST);
            break;
        case 'ti.staff.delete':
            $staffController->delete($_POST);
            break;
        case 'ti.assets.store':
            $assetController->store($_POST);
            break;
        case 'ti.assets.update':
            $assetController->update($_POST);
            break;
        case 'ti.assets.delete':
            $assetController->delete($_POST);
            break;
        case 'ti.assets.transfer':
            $assetController->transfer($_POST);
            break;
        case 'ti.assets.quick-department.store':
            $assetController->quickStoreDepartment($_POST);
            break;
        case 'ti.assets.quick-staff.store':
            $assetController->quickStoreStaff($_POST);
            break;
        case 'commercial.kanban.board.store':
            $commercialKanbanController->createBoard($_POST);
            break;
        case 'commercial.kanban.board.update':
            $commercialKanbanController->updateBoard($_POST);
            break;
        case 'commercial.kanban.members.update':
            $commercialKanbanController->updateMembers($_POST);
            break;
        case 'commercial.kanban.task.store':
            $commercialKanbanController->createTask($_POST);
            break;
        case 'commercial.kanban.task.update':
            $commercialKanbanController->updateTask($_POST);
            break;
        case 'commercial.kanban.task.move':
            $commercialKanbanController->moveTask($_POST);
            break;
        case 'commercial.kanban.task.delete':
            $commercialKanbanController->deleteTask($_POST);
            break;
        case 'ti.home-requests.store':
            $homeRequestController->store($_POST);
            break;
        case 'ti.home-requests.approve':
            $homeRequestController->approve($_POST);
            break;
        case 'ti.home-requests.reject':
            $homeRequestController->reject($_POST);
            break;
        case 'ti.home-requests.return':
            $homeRequestController->markReturned($_POST);
            break;
        case 'ti.contracts.update':
            $contractController->update($_POST);
            break;
        case 'ti.settings.categories.store':
            $tiSettingsController->storeCategory($_POST);
            break;
        case 'ti.settings.categories.update':
            $tiSettingsController->updateCategory($_POST);
            break;
        case 'ti.settings.categories.delete':
            $tiSettingsController->deleteCategory($_POST);
            break;
        case 'ti.settings.contract-types.store':
            $tiSettingsController->storeContractType($_POST);
            break;
        case 'ti.settings.contract-types.update':
            $tiSettingsController->updateContractType($_POST);
            break;
        case 'ti.settings.contract-types.delete':
            $tiSettingsController->deleteContractType($_POST);
            break;
        case 'ti.settings.statuses.store':
            $tiSettingsController->storeStatus($_POST);
            break;
        case 'ti.settings.statuses.update':
            $tiSettingsController->updateStatus($_POST);
            break;
        case 'ti.settings.statuses.delete':
            $tiSettingsController->deleteStatus($_POST);
            break;
        case 'ti.settings.departments.store':
            $tiSettingsController->storeDepartment($_POST);
            break;
        case 'ti.settings.departments.update':
            $tiSettingsController->updateDepartment($_POST);
            break;
        case 'ti.settings.departments.delete':
            $tiSettingsController->deleteDepartment($_POST);
            break;
        case 'ti.settings.users.store':
            $tiSettingsController->storeUser($_POST);
            break;
        case 'ti.settings.users.update':
            $tiSettingsController->updateUser($_POST);
            break;
        default:
            http_response_code(404);
            echo 'Rota POST nao encontrada';
    }
    exit;
}

switch ($route) {
    case 'login':
        $authController->loginForm();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'areas':
        $areaController->index();
        break;
    case 'ti.dashboard':
        $dashboardController->index();
        break;
    case 'ti.staff':
        $staffController->index();
        break;
    case 'ti.assets':
        $assetController->index();
        break;
    case 'ti.settings':
        $tiSettingsController->index();
        break;
    case 'ti.contracts':
        $contractController->index();
        break;
    case 'ti.home-requests':
        $homeRequestController->index();
        break;
    case 'commercial.kanban':
        $commercialKanbanController->index();
        break;

    // Legacy routes
    case 'dashboard':
        View::redirect('ti.dashboard');
        break;
    case 'staff':
        View::redirect('ti.staff');
        break;
    case 'assets':
        View::redirect('ti.assets');
        break;

    default:
        http_response_code(404);
        echo 'Rota nao encontrada';
}
