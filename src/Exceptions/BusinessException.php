<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando se vulnera una regla de negocio del dominio.
 */
class BusinessException extends Exception
{
}
