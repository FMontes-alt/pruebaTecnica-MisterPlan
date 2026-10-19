<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando no se encuentra un recurso solicitado (mapea a HTTP 404).
 */
class NotFoundException extends Exception
{
}
