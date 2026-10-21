<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Exceptions\NotFoundException;
use App\Services\ReservaService;

/**
 * Controlador de Vistas Web (Twig).
 * Renderiza vistas en servidor y delega la interactividad asíncrona a JavaScript Vanilla.
 */
class ReservaViewController
{
    private ReservaService $service;

    public function __construct(?ReservaService $service = null)
    {
        $this->service = $service ?? new ReservaService();
    }

    /**
     * Renderiza el listado principal de reservas con sus filtros.
     */
    public function index(Request $request): void
    {
        $filters = $request->getQueryParams();
        $reservas = $this->service->listarReservas($filters);
        $stats = $this->service->obtenerEstadisticas();

        $html = View::render('reservas/index.html.twig', [
            'reservas' => $reservas,
            'filters'  => $filters,
            'stats'    => $stats,
        ]);

        Response::html($html);
    }

    /**
     * Renderiza la vista de detalle de una reserva con su historial de eventos.
     */
    public function show(Request $request, string $id): void
    {
        $reservaId = filter_var($id, FILTER_VALIDATE_INT);

        if ($reservaId === false || $reservaId <= 0) {
            Response::redirect('/', 302);
            return;
        }

        try {
            $data = $this->service->obtenerDetalle($reservaId);

            $html = View::render('reservas/detalle.html.twig', [
                'reserva' => $data['reserva'],
                'eventos' => $data['eventos'],
            ]);

            Response::html($html);
        } catch (NotFoundException $e) {
            Response::html(
                View::render('errors/404.html.twig', [
                    'message' => $e->getMessage()
                ]),
                404
            );
        }
    }
}
