<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Repositorio del Historial de Eventos de Reservas.
 */
class EventoRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Obtiene todos los eventos de una reserva ordenados cronológicamente.
     */
    public function findByReservaId(int $reservaId): array
    {
        $sql = "SELECT id, reserva_id, tipo, descripcion, fecha_creacion
                FROM reserva_evento
                WHERE reserva_id = :reserva_id
                ORDER BY fecha_creacion ASC, id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reserva_id' => $reservaId]);

        return $stmt->fetchAll();
    }

    /**
     * Registra un nuevo evento en el historial.
     */
    public function create(int $reservaId, string $tipo, string $descripcion): int
    {
        $sql = "INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion)
                VALUES (:reserva_id, :tipo, :descripcion, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':reserva_id' => $reservaId,
            ':tipo' => strtoupper(trim($tipo)),
            ':descripcion' => trim($descripcion),
        ]);

        return (int) $this->db->lastInsertId();
    }
}
