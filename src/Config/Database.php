<?php

declare(strict_types=1);

namespace App\Config;

use PDO, PDOException, RuntimeException;

/**
 * Gestor de conexion a base de datos con PDO.
 * Patrón Singelton
 */
class Database
{
    private static ?PDO $pdo = null;
    private function __construct()
    {
        // evitar instantacion directa
    }

    /**
     * Obtiene la instancia activa de PDO.
     * Soporta variables de entorno o valores por defecto para desarrollo local (XAMPP/MAMP).
     * @return PDO
     * @throws RuntimeException
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            // Cargar archivo .env si existe en la raíz
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                        continue;
                    }
                    [$key, $val] = explode('=', $trimmed, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");
                    $_ENV[$key] = $val;
                    putenv("$key=$val");
                }
            }

            // Cargar variables de entorno
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
            $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'misterplan_reservas';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
            $password = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

            $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Previene inyecciones SQL
                PDO::ATTR_STRINGIFY_FETCHES  => false, // Mantiene tipos nativos en el fetch
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                // Registro interno sin exponer credenciales al exterior
                error_log('[DB Error]: ' . $e->getMessage());
                throw new RuntimeException("Error de conexión a BD: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}