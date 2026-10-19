<?php

declare(strict_types=1);

// Servidor envevido PHP
if (php_sapi_name() === 'cli-server'){
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $filePath = __DIR__ . $path; 
    if ($path !=='/' && file_exists($filePath) && !is_dir($filePath)){
        return false;
    }
}

// Cargar el autoloader de Composer para cargar automaticamente las dependencias
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\ReservaApiController;
use App\Controllers\ReservaViewController;
use App\Core\Request;
use App\Core\Router;

// Incializar el router
$router = new Router();


// ==========================================================
// RUTAS WEB (Renderizadas en servidor con Twig)
// ==========================================================
$router->get('/', [ReservaViewController::class, 'index']);
$router->get('/reservas', [ReservaViewController::class, 'index']);
$router->get('/reservas/{id}', [ReservaViewController::class, 'show']);

// ==========================================================
// RUTAS API REST (JSON)
// ==========================================================
$router->get('/api/reservas', [ReservaApiController::class, 'index']);
$router->get('/api/reservas/{id}', [ReservaApiController::class, 'show']);
$router->post('/api/reservas', [ReservaApiController::class, 'store']);
$router->patch('/api/reservas/{id}/estado', [ReservaApiController::class, 'updateEstado']);
$router->post('/api/reservas/{id}/estado', [ReservaApiController::class, 'updateEstado']); // Soporte adicional POST

// Finalizar la petición entrante
$request = new Request();
$router->dispatch($request);