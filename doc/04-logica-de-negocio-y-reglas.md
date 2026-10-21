# 04 — Lógica de Negocio, Reglas y Transacciones

Este documento explica las **reglas del mundo real** que gobiernan las reservas en nuestra aplicación. Están programadas en `src/Services/ReservaService.php` y son el núcleo de lo que evalúa MisterPlan.

---

## 🚦 1. La Máquina de Estados (State Machine)

Una reserva no puede cambiar de estado alocadamente. Sigue una máquina de estados estricta:

```text
               ┌──────────────┐
               │  PENDIENTE   │  (Estado inicial al crearse)
               └──────┬───────┘
                      │
           ┌──────────┴──────────┐
           │                     │
           ▼                     ▼
    ┌──────────────┐      ┌──────────────┐
    │  CONFIRMADA  │      │  CANCELADA   │
    └──────┬───────┘      └──────────────┘
           │                     ▲
           │                     │
           └─────────────────────┘
                 (Una reserva confirmada
                  puede ser cancelada)
```

### Reglas aplicadas en el código:
1. **De `pendiente` a `confirmada`:** Permitido (cuando el cliente realiza el pago o aporta tarjeta).
2. **De `pendiente` a `cancelada`:** Permitido (si el cliente desiste o expira el plazo).
3. **De `confirmada` a `cancelada`:** Permitido (anulación de reserva con motivo).
4. **Reserva `cancelada` es un estado terminal:**
   - Una reserva cancelada **jamás puede volver a confirmarse ni a cancelarse**.
   - Si alguien lo intenta, el sistema bloquea la acción y lanza una excepción:  
     `BusinessException: "No se puede modificar el estado de una reserva cancelada."`
5. **No se permite cambiar al mismo estado:** Si ya está confirmada y se envía otra petición para confirmarla, se rechaza.

---

## 🔍 2. Validaciones en el Servidor

Nunca confiamos en lo que envía el navegador del usuario (porque cualquiera podría saltarse el JavaScript o usar herramientas como Postman o cURL). Por eso, `ReservaService::validarDatos()` comprueba:

1. **Campos obligatorios:** Nombre del huésped, email, nombre del alojamiento, fecha de entrada, fecha de salida e importe.
2. **Validación del Email:**
   ```php
   filter_var($email, FILTER_VALIDATE_EMAIL)
   ```
   Comprueba que tenga formato real con `@` y dominio válido.
3. **Coherencia temporal de las Fechas:**
   - Ambas fechas deben tener formato válido `AAAA-MM-DD`.
   - La fecha de salida debe ser **estrictamente posterior** a la fecha de entrada:
     ```php
     if ($dSalida <= $dEntrada) {
         $errors['fecha_salida'] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
     }
     ```
4. **Importe Económico:**
   - Debe ser un valor numérico y estrictamente positivo (`> 0`). No se permiten reservas gratis (0 €) ni con saldo negativo.

---

## 🛡️ 3. Transacciones Atómicas en Base de Datos (ACID)

En el software de reservas y pagos, **la atomicidad es sagrada**: o se guarda todo con éxito, o no se guarda absolutamente nada.

### El problema de no usar transacciones:
Imagina que guardas la reserva en la tabla `reserva` con éxito, pero justo al ir a guardar el evento en `reserva_evento`, el servidor se queda sin memoria o falla la red. Te quedaría una reserva sin historial ni auditoría.

### Cómo lo resolvemos con PDO:
En `ReservaService.php` envolvemos cada operación dentro de una transacción:

```php
// 1. Abrimos la transacción
$this->db->beginTransaction();

try {
    // 2. Insertamos la reserva
    $nuevoId = $this->reservaRepo->create($data);

    // 3. Insertamos el evento en el historial
    $this->eventoRepo->create($nuevoId, 'CREACION', 'Reserva registrada...');

    // 4. Si todo ha salido perfecto, confirmamos definitivamente en MySQL
    $this->db->commit();

    return $this->reservaRepo->findById($nuevoId);

} catch (Throwable $e) {
    // 5. Si algo falla, DESHACEMOS cualquier cambio previo como si nunca hubiera pasado
    $this->db->rollBack();
    throw $e;
}
```

---

## 🚨 4. Gestión de Excepciones Específicas

Para no devolver errores genéricos que confundan al cliente o al frontend, creamos tres tipos de excepciones en `src/Exceptions/`:

1. **`ValidationException`**:
   - Salta cuando un dato es incorrecto (email mal escrito, fecha incoherente).
   - Contiene un array con el detalle de qué campo específico falló.
   - El controlador la captura y devuelve un código HTTP **`422 Unprocessable Content`**.
2. **`NotFoundException`**:
   - Salta cuando se pide una reserva con un ID que no existe en la base de datos (ej: `/reservas/99999`).
   - El controlador la captura y devuelve un código HTTP **`404 Not Found`**.
3. **`BusinessException`**:
   - Salta cuando se intenta vulnerar una regla de negocio (ej: modificar una reserva cancelada).
   - El controlador la captura y devuelve un código HTTP **`400 Bad Request`**.
