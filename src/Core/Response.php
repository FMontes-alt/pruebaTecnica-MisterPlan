<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gestor de respuestas HTTP.
 * Emite cabeceras, códigos de estado adecuados y cuerpos JSON o HTML.
 */
class Response
{
    /**
     * Devuelve una respuesta JSON estandarizada con código HTTP.
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Devuelve un error formateado en JSON con código HTTP y detalles opcionales.
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $statusCode);
    }

    /**
     * Devuelve una respuesta con contenido HTML.
     */
    public static function html(string $html, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /**
     * Redirección HTTP.
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
}
