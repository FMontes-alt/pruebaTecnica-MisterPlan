<?php

namespace App\Tests;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Repositories\EventoRepository;
use App\Repositories\ReservaRepository;
use App\Services\ReservaService;
use PHPUnit\Framework\TestCase;

class ReservaServiceTest extends TestCase
{
    private ReservaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Instancia con mocks para pruebas unitarias aisladas (sin dependencia de MySQL activo)
        $pdoMock = $this->createMock(\PDO::class);
        $reservaRepoMock = $this->createMock(ReservaRepository::class);
        $eventoRepoMock = $this->createMock(EventoRepository::class);
        $this->service = new ReservaService($reservaRepoMock, $eventoRepoMock, $pdoMock);
    }

    /**
     * @test
     * Verifica que se rechacen datos cuando faltan campos obligatorios.
     */
    public function testValidacionCamposObligatorios(): void
    {
        $this->expectException(ValidationException::class);

        $datosIncompletos = [
            'nombre_huesped' => '',
            'email_huesped'  => '',
        ];

        $this->service->validarDatos($datosIncompletos);
    }

    /**
     * @test
     * Verifica que se rechace un email con formato inválido.
     */
    public function testValidacionEmailInvalido(): void
    {
        $this->expectException(ValidationException::class);

        $datos = [
            'nombre_huesped'     => 'Juan Pérez',
            'email_huesped'      => 'email-no-valido-sin-arroba',
            'nombre_alojamiento' => 'Casa Rural El Castañar',
            'fecha_entrada'      => '2026-11-01',
            'fecha_salida'       => '2026-11-05',
            'importe'            => 150.00,
        ];

        $this->service->validarDatos($datos);
    }

    /**
     * @test
     * Verifica que la fecha de salida deba ser estrictamente posterior a la de entrada.
     */
    public function testValidacionFechasIncoherentes(): void
    {
        $this->expectException(ValidationException::class);

        $datos = [
            'nombre_huesped'     => 'Laura Gómez',
            'email_huesped'      => 'laura.gomez@test.com',
            'nombre_alojamiento' => 'Hotel Playa Azul',
            'fecha_entrada'      => '2026-11-10',
            'fecha_salida'       => '2026-11-05', // Anterior a la entrada
            'importe'            => 220.00,
        ];

        $this->service->validarDatos($datos);
    }

    /**
     * @test
     * Verifica que el importe deba ser estrictamente positivo.
     */
    public function testValidacionImporteNegativoOCero(): void
    {
        $this->expectException(ValidationException::class);

        $datos = [
            'nombre_huesped'     => 'Laura Gómez',
            'email_huesped'      => 'laura.gomez@test.com',
            'nombre_alojamiento' => 'Hotel Playa Azul',
            'fecha_entrada'      => '2026-11-01',
            'fecha_salida'       => '2026-11-05',
            'importe'            => -50.00, // Negativo
        ];

        $this->service->validarDatos($datos);
    }

    /**
     * @test
     * Verifica que datos válidos pasen la validación sin lanzar excepción.
     */
    public function testValidacionDatosCorrectos(): void
    {
        $datos = [
            'nombre_huesped'     => 'Carlos Rodríguez',
            'email_huesped'      => 'carlos@ejemplo.com',
            'nombre_alojamiento' => 'Apartamentos Sol y Montaña',
            'fecha_entrada'      => '2026-12-01',
            'fecha_salida'       => '2026-12-08',
            'importe'            => 450.00,
            'estado'             => 'pendiente',
        ];

        // No debe lanzar excepción
        $this->service->validarDatos($datos);
        $this->assertTrue(true);
    }

    /**
     * @test
     * Verifica que no se pueda modificar el estado de una reserva ya cancelada.
     */
    public function testNoSePuedeModificarReservaCancelada(): void
    {
        $reservaRepoMock = $this->createMock(ReservaRepository::class);
        $eventoRepoMock = $this->createMock(EventoRepository::class);

        $reservaRepoMock->method('findById')
            ->willReturn([
                'id' => 99,
                'nombre_huesped' => 'Cliente Cancelado',
                'estado' => 'cancelada'
            ]);

        $serviceConMocks = new ReservaService($reservaRepoMock, $eventoRepoMock);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage("No se puede modificar el estado de una reserva cancelada.");

        $serviceConMocks->cambiarEstado(99, 'confirmada');
    }

    /**
     * @test
     * Verifica que cambiar al mismo estado actual lance una excepción de negocio.
     */
    public function testNoPermiteCambiarAlMismoEstado(): void
    {
        $reservaRepoMock = $this->createMock(ReservaRepository::class);
        $eventoRepoMock = $this->createMock(EventoRepository::class);

        $reservaRepoMock->method('findById')
            ->willReturn([
                'id' => 10,
                'nombre_huesped' => 'Cliente Confirmado',
                'estado' => 'confirmada'
            ]);

        $serviceConMocks = new ReservaService($reservaRepoMock, $eventoRepoMock);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage("La reserva ya se encuentra en estado 'confirmada'.");

        $serviceConMocks->cambiarEstado(10, 'confirmada');
    }
}
