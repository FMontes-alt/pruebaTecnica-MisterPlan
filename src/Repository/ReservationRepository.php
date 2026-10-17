<?php

namespace App\Repository;
use App\Config\Database;
use PDO;

class ReservationRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(array $filters = [])
    {
        $sql = "SELECT id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion
                FROM reserva
                WHERE 1 = 1";

        $params = [];

        // Filtro por fecha desde (a partir de la fecha de entrada)
        if (!empty($filters['fecha_desde'])) {
            $sql .= " AND fecha_entrada >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        // Filtro por fecha hasta (hasta la fecha de salida)
        if (!empty($filters['fecha_hasta'])) {
            $sql .= " AND fecha_salida <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        // Búsqueda por nombre de huésped (o alojamiento/email para mejor UX)
        if (!empty($filters['search'])) {
            $sql .= " AND (nombre_huesped LIKE :search OR email_huesped LIKE :search OR nombre_alojamiento LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY fecha_creacion DESC, id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $sql = "SELECT id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion
                FROM reserva
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO reserva 
                (nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion)
                VALUES 
                (:nombre_huesped, :email_huesped, :nombre_alojamiento, :fecha_entrada, :fecha_salida, :estado, :importe, :notas, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre_huesped' => $data['nombre_huesped'],
            ':email_huesped' => $data['email_huesped'],
            ':nombre_alojamiento' => $data['nombre_alojamiento'],
            ':fecha_entrada' => $data['fecha_entrada'],
            ':fecha_salida' => $data['fecha_salida'],
            ':estado' => $data['estado'] ?? 'pendiente',
            ':importe' => $data['importe'],
            ':notas' => !empty($data['notas']) ? trim($data['notas']) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nuevoEstado): int
    {
        $sql = "UPDATE reserva SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado' => $nuevoEstado,
            ':id' => $id,
        ]);
    }

    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM reserva WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                    COALESCE(SUM(CASE WHEN estado = 'confirmada' THEN importe ELSE 0 END), 0) as total_facturado
                FROM reserva";

        $stmt = $this->db->query($sql);
        $res = $stmt->fetch();

        return [
            'total' => (int) ($res['total'] ?? 0),
            'confirmadas' => (int) ($res['confirmadas'] ?? 0),
            'pendientes' => (int) ($res['pendientes'] ?? 0),
            'canceladas' => (int) ($res['canceladas'] ?? 0),
            'total_facturado' => (float) ($res['total_facturado'] ?? 0),
        ];
    }
}