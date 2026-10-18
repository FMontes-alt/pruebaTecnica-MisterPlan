<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use Throwable;

/**
 * Enrutador HTTP nativo ligero y extensible.
 * Soporta parámetros dinámicos ({id}), verbos REST y manejo centralizado de excepciones.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function patch(string $path, array $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function put(string $path, array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        // Normalizar path
        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Convertir ruta con parámetros {id} a expresión regular
        // Ejemplo: /api/reservas/{id} -> #^/api/reservas/(?P<id>[^/]+)$#
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'regex'   => $regex,
            'handler' => $handler,
        ];
    }

    /**
     * Despacha la petición recibida ejecutando el controlador correspondiente.
     */
    public function dispatch(Request $request): void
    {
        $requestMethod = strtoupper($request->getMethod());
        $requestPath = '/' . trim($request->getPath(), '/');
        if ($requestPath === '') {
            $requestPath = '/';
        }

        $matchedRoute = null;
        $params = [];
        $methodAllowed = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $requestPath, $matches)) {
                if ($route['method'] === $requestMethod) {
                    $matchedRoute = $route;
                    // Extraer únicamente los parámetros con nombre
                    $params = array_filter($matches, function ($key) {
                        return !is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                    break;
                } else {
                    $methodAllowed = true;
                }
            }
        }

        try {
            if ($matchedRoute) {
                [$controllerClass, $method] = $matchedRoute['handler'];
                $controller = new $controllerClass();
                
                // Ejecuta el método pasando Request y los parámetros de la URL
                $controller->$method($request, ...array_values($params));
                return;
            }

            if ($methodAllowed) {
                $this->handleMethodNotAllowed($request);
                return;
            }

            $this->handleNotFound($request);
        } catch (Throwable $e) {
            $this->handleException($request, $e);
        }
    }

    private function handleNotFound(Request $request): void
    {
        if (str_starts_with($request->getPath(), '/api')) {
            Response::error('Ruta o recurso no encontrado.', 404);
        } else {
            Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404 - No Encontrado</title>' .
                '<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f8fafc;color:#334155;}' .
                'h1{font-size:4rem;margin-bottom:0.5rem;color:#e11d48;}a{color:#0284c7;text-decoration:none;font-weight:bold;}</style>' .
                '</head><body><h1>404</h1><p>La página que buscas no existe.</p><p><a href="/">Volver al listado</a></p></body></html>',
                404
            );
        }
    }

    private function handleMethodNotAllowed(Request $request): void
    {
        if (str_starts_with($request->getPath(), '/api')) {
            Response::error('Método HTTP no permitido para esta ruta.', 405);
        } else {
            Response::html('<h1>405 Método No Permitido</h1>', 405);
        }
    }

    private function handleException(Request $request, Throwable $e): void
    {
        $isApi = str_starts_with($request->getPath(), '/api');

        if ($e instanceof ValidationException) {
            if ($isApi) {
                Response::error($e->getMessage(), 422, $e->getErrors());
            } else {
                Response::html('<h1>422 Error de validación</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 422);
            }
            return;
        }

        if ($e instanceof NotFoundException) {
            if ($isApi) {
                Response::error($e->getMessage(), 404);
            } else {
                Response::html('<h1>404 No encontrado</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
            }
            return;
        }

        if ($e instanceof BusinessException) {
            if ($isApi) {
                Response::error($e->getMessage(), 400);
            } else {
                Response::html('<h1>400 Regla de negocio no permitida</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 400);
            }
            return;
        }

        // En producción no se muestran detalles sensibles, solo mensaje genérico
        $errorMessage = $e->getMessage();
        if ($isApi) {
            Response::error("Error interno del servidor: {$errorMessage}", 500);
        } else {
            Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error 500</title>' .
                '<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#fef2f2;color:#991b1b;}' .
                'h1{font-size:3rem;}p{font-size:1.1rem;}</style></head><body>' .
                '<h1>Error 500</h1><p>Ocurrió un error inesperado al procesar tu solicitud.</p>' .
                '<p><em>' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</em></p>' .
                '<p><a href="/">Volver al inicio</a></p></body></html>',
                500
            );
        }
    }
}
