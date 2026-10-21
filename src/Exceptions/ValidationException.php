<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando los datos de entrada no superan la validación (mapea a HTTP 422 o 400).
 */
class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Error de validación en los datos proporcionados.', int $code = 422)
    {
        $this->errors = $errors;
        parent::__construct($message, $code);
    }

    /**
     * Obtiene el listado de errores de validación por campo.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
