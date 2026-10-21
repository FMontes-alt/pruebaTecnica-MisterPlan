<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\EventoRepository;
use App\Repositories\ReservaRepository;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Capa de Lógica de Negocio y Dominio para la Gestión de Reservas.
 * Garantiza validaciones estrictas y operaciones transaccionales (ACID).
 */
class ReservaService
{
    /** @var string[] Estados válidos del ciclo de vida de una reserva */
    private const ESTADOS_VALIDOS = ['pendiente', 'confirmada', 'cancelada'];

    private PDO $db;
    private ReservaRepository $reservaRepo;
    private EventoRepository $eventoRepo;

    public function __construct(
        ?ReservaRepository $reservaRepo = null,
        ?EventoRepository $eventoRepo = null,
        ?PDO $db = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->reservaRepo = $reservaRepo ?? new ReservaRepository($this->db);
        $this->eventoRepo = $eventoRepo ?? new EventoRepository($this->db);
    }

    /**
     * Obtiene el listado de reservas aplicando filtros sanitizados.
     */
    public function listarReservas(array $filters = []): array
    {
        $sanitizedFilters = [
            'estado' => isset($filters['estado']) && in_array($filters['estado'], self::ESTADOS_VALIDOS, true)
                ? $filters['estado'] : null,
            'fecha_desde' => !empty($filters['fecha_desde']) ? $filters['fecha_desde'] : null,
            'fecha_hasta' => !empty($filters['fecha_hasta']) ? $filters['fecha_hasta'] : null,
            'search' => !empty($filters['search']) ? trim($filters['search']) : null,
        ];

        return $this->reservaRepo->findAll($sanitizedFilters);
    }

    /**
     * Obtiene métricas agregadas del panel de control.
     */
    public function obtenerEstadisticas(): array
    {
        return $this->reservaRepo->getStats();
    }

    /**
     * Obtiene la reserva y su historial completo de eventos.
     *
     * @throws NotFoundException si la reserva no existe
     */
    public function obtenerDetalle(int $id): array
    {
        $reserva = $this->reservaRepo->findById($id);

        if (!$reserva) {
            throw new NotFoundException("La reserva con ID #{$id} no existe.");
        }

        $eventos = $this->eventoRepo->findByReservaId($id);

        return [
            'reserva' => $reserva,
            'eventos' => $eventos,
        ];
    }

    /**
     * Registra una nueva reserva y su evento inicial de forma atómica.
     *
     * @throws ValidationException si los datos no superan la validación
     * @throws RuntimeException si no se puede recuperar la reserva recién creada
     */
    public function crearReserva(array $data): array
    {
        $this->validarDatos($data);

        $estado = !empty($data['estado']) ? strtolower(trim($data['estado'])) : 'pendiente';
        $data['estado'] = $estado;

        $this->db->beginTransaction();

        try {
            $nuevoId = $this->reservaRepo->create($data);

            $descripcionEvento = "Reserva registrada inicialmente en estado '{$estado}'.";
            if (!empty($data['notas'])) {
                $descripcionEvento .= " Observaciones: " . trim($data['notas']);
            }

            $this->eventoRepo->create($nuevoId, 'CREACION', $descripcionEvento);

            $this->db->commit();

            $reserva = $this->reservaRepo->findById($nuevoId);
            if (!$reserva) {
                throw new RuntimeException("No se pudo recuperar la reserva recién creada (ID: #{$nuevoId}).");
            }

            return $reserva;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Modifica el estado de una reserva (confirmar o cancelar) y añade el evento al historial.
     *
     * @throws ValidationException si el estado proporcionado no es válido
     * @throws NotFoundException si la reserva no existe
     * @throws BusinessException si se vulnera una regla del ciclo de vida de la reserva
     * @throws RuntimeException si no se puede recuperar la reserva actualizada
     */
    public function cambiarEstado(int $id, string $nuevoEstado, ?string $motivo = null): array
    {
        $nuevoEstado = strtolower(trim($nuevoEstado));

        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            throw new ValidationException([
                'estado' => "El estado '{$nuevoEstado}' no es válido. Debe ser: " . implode(', ', self::ESTADOS_VALIDOS) . "."
            ]);
        }

        $reserva = $this->reservaRepo->findById($id);
        if (!$reserva) {
            throw new NotFoundException("La reserva con ID #{$id} no existe.");
        }

        $estadoActual = $reserva['estado'];

        // Reglas de negocio sobre la máquina de estados
        if ($estadoActual === 'cancelada') {
            throw new BusinessException("No se puede modificar el estado de una reserva cancelada.");
        }

        if ($estadoActual === $nuevoEstado) {
            throw new BusinessException("La reserva ya se encuentra en estado '{$nuevoEstado}'.");
        }

        $tipoEvento = match ($nuevoEstado) {
            'confirmada' => 'CONFIRMACION',
            'cancelada'  => 'CANCELACION',
            default      => 'CAMBIO_ESTADO',
        };

        $descripcionEvento = "Cambio de estado: '{$estadoActual}' → '{$nuevoEstado}'.";
        if (!empty($motivo)) {
            $descripcionEvento .= " Motivo / Detalle: " . trim($motivo);
        }

        $this->db->beginTransaction();

        try {
            $this->reservaRepo->updateEstado($id, $nuevoEstado);
            $this->eventoRepo->create($id, $tipoEvento, $descripcionEvento);
            $this->db->commit();

            $reservaActualizada = $this->reservaRepo->findById($id);
            if (!$reservaActualizada) {
                throw new RuntimeException("No se pudo recuperar la reserva actualizada (ID: #{$id}).");
            }

            return $reservaActualizada;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Valida exhaustivamente los datos de una reserva en el servidor.
     *
     * @throws ValidationException con el listado de errores por campo
     */
    public function validarDatos(array $data): void
    {
        $errors = [];

        // 1. Nombre de huésped
        $nombre = trim($data['nombre_huesped'] ?? '');
        if ($nombre === '') {
            $errors['nombre_huesped'] = 'El nombre del huésped es obligatorio.';
        } elseif (mb_strlen($nombre) < 3) {
            $errors['nombre_huesped'] = 'El nombre del huésped debe tener al menos 3 caracteres.';
        } elseif (mb_strlen($nombre) > 150) {
            $errors['nombre_huesped'] = 'El nombre del huésped no puede superar los 150 caracteres.';
        }

        // 2. Email de huésped
        $email = trim($data['email_huesped'] ?? '');
        if ($email === '') {
            $errors['email_huesped'] = 'El email del huésped es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email_huesped'] = 'El email proporcionado no tiene un formato válido.';
        } elseif (mb_strlen($email) > 150) {
            $errors['email_huesped'] = 'El email no puede superar los 150 caracteres.';
        }

        // 3. Nombre del alojamiento
        $alojamiento = trim($data['nombre_alojamiento'] ?? '');
        if ($alojamiento === '') {
            $errors['nombre_alojamiento'] = 'El nombre del alojamiento es obligatorio.';
        } elseif (mb_strlen($alojamiento) < 3) {
            $errors['nombre_alojamiento'] = 'El nombre del alojamiento debe tener al menos 3 caracteres.';
        }

        // 4. Fechas de entrada y salida
        $fechaEntrada = trim($data['fecha_entrada'] ?? '');
        $fechaSalida  = trim($data['fecha_salida'] ?? '');

        $dEntrada = DateTime::createFromFormat('Y-m-d', $fechaEntrada);
        $dSalida  = DateTime::createFromFormat('Y-m-d', $fechaSalida);

        if (!$fechaEntrada || !$dEntrada || $dEntrada->format('Y-m-d') !== $fechaEntrada) {
            $errors['fecha_entrada'] = 'La fecha de entrada es obligatoria y debe tener formato AAAA-MM-DD.';
        }

        if (!$fechaSalida || !$dSalida || $dSalida->format('Y-m-d') !== $fechaSalida) {
            $errors['fecha_salida'] = 'La fecha de salida es obligatoria y debe tener formato AAAA-MM-DD.';
        }

        if ($dEntrada && $dSalida && $dSalida <= $dEntrada) {
            $errors['fecha_salida'] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
        }

        // 5. Importe
        $importe = $data['importe'] ?? null;
        if ($importe === null || $importe === '') {
            $errors['importe'] = 'El importe es obligatorio.';
        } elseif (!is_numeric($importe) || (float) $importe <= 0) {
            $errors['importe'] = 'El importe debe ser un número decimal mayor que 0.';
        }

        // 6. Estado (si se proporciona explícitamente)
        if (!empty($data['estado'])) {
            $estado = strtolower(trim($data['estado']));
            if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
                $errors['estado'] = "El estado debe ser: " . implode(', ', self::ESTADOS_VALIDOS) . ".";
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
