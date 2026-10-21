<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Wrapper para el motor de plantillas Twig.
 * Gestiona la carga de vistas con herencia y auto-escaping por defecto.
 */
class View
{
    private static ?Environment $twig = null;

    /**
     * Inicializa la instancia de Twig si no existe.
     */
    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            $templatesPath = dirname(__DIR__, 2) . '/templates';
            $loader = new FilesystemLoader($templatesPath);

            self::$twig = new Environment($loader, [
                'cache' => false, // Desactivado para desarrollo ágil
                'auto_reload' => true,
                'autoescape' => 'html', // Protección XSS activada por defecto
            ]);

            // Variables globales accesibles desde cualquier plantilla
            self::$twig->addGlobal('app_name', 'MisterPlan Reservas');
            self::$twig->addGlobal('current_year', date('Y'));
        }

        return self::$twig;
    }

    /**
     * Renderiza una plantilla Twig devolviendo el HTML generado.
     */
    public static function render(string $template, array $data = []): string
    {
        return self::getTwig()->render($template, $data);
    }
}
