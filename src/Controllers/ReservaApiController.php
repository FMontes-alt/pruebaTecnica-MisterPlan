<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\ReservaService;
use Throwable;

/**
 * Controlador API REST para el recurso Reservas.
 * Emite respuestas en formato JSON estandarizado y códigos HTTP semánticos.
 */
class ReservaApiController
{
    private ReservaService $service;

    public function __construct(?ReservaService $service = null)
    {
        $this->service = $service ?? new ReservaService();
    }

    /**
     * GET /api/reservas
     * Lista reservas aplicando filtros combinables.
     */
    public function index(Request $request): void
    {
        $filters = $request->getQueryParams();
        $reservas = $this->service->listarReservas($filters);
        $stats = $this->service->obtenerEstadisticas();

        Response::json([
            'success' => true,
            'count'   => count($reservas),
            'stats'   => $stats,
            'data'    => $reservas,
        ], 200);
    }

    /**
     * GET /api/reservas/{id}
     * Devuelve el detalle de una reserva junto con su historial de eventos.
     */
    public function show(Request $request, string $id): void
    {
        $reservaId = filter_var($id, FILTER_VALIDATE_INT);

        if ($reservaId === false || $reservaId <= 0) {
            Response::error("El identificador de reserva debe ser un número entero positivo.", 400);
            return;
        }

        try {
            $data = $this->service->obtenerDetalle($reservaId);

            Response::json([
                'success' => true,
                'data'    => $data,
            ], 200);
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /**
     * POST /api/reservas
     * Registra una nueva reserva.
     */
    public function store(Request $request): void
    {
        $body = $request->getBody();

        try {
            $nuevaReserva = $this->service->crearReserva($body);

            Response::json([
                'success' => true,
                'message' => 'Reserva creada exitosamente.',
                'data'    => $nuevaReserva,
            ], 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getErrors());
        } catch (Throwable $e) {
            Response::error("Error al procesar la reserva: " . $e->getMessage(), 500);
        }
    }

    /**
     * PATCH /api/reservas/{id}/estado
     * Modifica el estado de una reserva (confirmar o cancelar).
     */
    public function updateEstado(Request $request, string $id): void
    {
        $reservaId = filter_var($id, FILTER_VALIDATE_INT);

        if ($reservaId === false || $reservaId <= 0) {
            Response::error("El identificador de reserva debe ser un número entero válido.", 400);
            return;
        }

        $body = $request->getBody();
        $nuevoEstado = $body['estado'] ?? '';
        $motivo = $body['motivo'] ?? null;

        if (empty($nuevoEstado)) {
            Response::error("El campo 'estado' es requerido (confirmada o cancelada).", 400);
            return;
        }

        try {
            $reservaActualizada = $this->service->cambiarEstado($reservaId, $nuevoEstado, $motivo);

            Response::json([
                'success' => true,
                'message' => "Estado de la reserva actualizado a '{$nuevoEstado}'.",
                'data'    => $reservaActualizada,
            ], 200);
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);
        } catch (BusinessException|ValidationException $e) {
            $errors = ($e instanceof ValidationException) ? $e->getErrors() : [];
            Response::error($e->getMessage(), 400, $errors);
        } catch (Throwable $e) {
            Response::error("Error al actualizar estado: " . $e->getMessage(), 500);
        }
    }
}
