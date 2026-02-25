<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function init(string $sessionName): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($sessionName);
            session_start();
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'] ?? 'operador',
            'department_id' => $user['department_id'] ?? null,
            'department_name' => $user['department_name'] ?? '',
            'allowed_routes' => $user['allowed_routes'] ?? [],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
