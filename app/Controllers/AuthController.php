<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(private UserRepository $users)
    {
    }

    public function loginForm(?string $error = null): void
    {
        if (Auth::check()) {
            View::redirect('areas');
        }

        View::render('auth/login', [
            'title' => 'Login',
            'error' => $error,
            'hideMenu' => true,
        ]);
    }

    public function login(array $input): void
    {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if ($username === '' || $password === '') {
            $this->loginForm('Informe usuário e senha.');
            return;
        }

        $user = $this->users->findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->loginForm('Credenciais inválidas.');
            return;
        }

        Auth::login($user);
        View::redirect('areas');
    }

    public function logout(): void
    {
        Auth::logout();
        View::redirect('login');
    }
}
