<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement('CREATE DATABASE IF NOT EXISTS `nutricional_testing_isolated` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
    echo "Base de datos `nutricional_testing_isolated` creada/verificada exitosamente.\n";
} catch (\Throwable $e) {
    echo "Error creando base de datos: " . $e->getMessage() . "\n";
}
