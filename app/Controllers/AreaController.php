<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class AreaController
{
    public function index(): void
    {
        View::render('areas/index', [
            'title' => 'Areas',
            'currentRoute' => 'areas',
        ]);
    }
}
