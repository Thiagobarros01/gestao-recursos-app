<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require __DIR__ . '/../Views/partials/header.php';
        require $viewFile;
        require __DIR__ . '/../Views/partials/footer.php';
    }

    public static function redirect(string $route): void
    {
        $route = trim($route);
        if ($route === '') {
            $route = 'areas';
        }

        [$routeName, $extra] = array_pad(explode('&', $route, 2), 2, '');
        $location = 'index.php?r=' . urlencode($routeName);
        if ($extra !== '') {
            $location .= '&' . $extra;
        }

        header('Location: ' . $location);
        exit;
    }
}
