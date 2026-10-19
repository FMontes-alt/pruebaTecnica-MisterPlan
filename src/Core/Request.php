<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $queryParams;
    private array $body;

    /**
     * Abstraer el HTTP request
     * Centralizar, limpar y seguro parametros, metodos y body JSON
     */
    public function __construct()
    {
        $this->method = strtolower($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Parsear URI eliminando la query string
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUrl = parse_url($uri, PHP_URL_PATH);
        $this->path = rawurldecode($parsedUrl ?: '/');

        // Parámetros de query string ($_GET)
        $this->queryParams = $_GET;

        // Parsear el cuerpo de la petición
        $this->parseBody();
    }

    /**
     * Procesa tanto formularios en POST y JSON
     * @return void
     */
    private function parseBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            $this->body = is_array($data) ? $data : [];
        } else {
            $this->body = $_POST;
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }
}