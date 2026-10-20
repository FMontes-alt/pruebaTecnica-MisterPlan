# 03 — Base de Datos y Modelo de Datos

Esta sección detalla cómo está estructurada la información en **MySQL / MariaDB**, qué tablas existen, cómo se relacionan entre sí y por qué se han tomado ciertas decisiones técnicas.

---

## 🗄️ Esquema Relacional

Nuestra base de datos se llama `misterplan_reservas` y utiliza el motor de almacenamiento **InnoDB** con codificación **`utf8mb4`** (para admitir tildes, caracteres especiales españoles y símbolos sin corromper el texto).

El modelo consta de **dos tablas relacionadas**:

```text
┌─────────────────────────────────┐
│            reserva              │
├─────────────────────────────────┤
│ PK  id                          │ 1
│     nombre_huesped              │ │
│     email_huesped               │ │
│     nombre_alojamiento          │ │
│     fecha_entrada               │ │
│     fecha_salida                │ │
│     estado                      │ │
│     importe                     │ │
│     notas                       │ │
│     fecha_creacion              │ │
└─────────────────────────────────┘ │
                                    │ (Relación 1 a Muchos: Una reserva tiene varios eventos)
                                    ▼ N
┌─────────────────────────────────┐
│         reserva_evento          │
├─────────────────────────────────┤
│ PK  id                          │
│ FK  reserva_id ─────────────────┘
│     tipo (CREACION, CONFIRMACION, CANCELACION)
│     descripcion
│     fecha_creacion
└─────────────────────────────────┘
```

---

## 📋 Detalle de las Tablas

### 1. Tabla `reserva`
Almacena la información principal de cada reserva turística:

| Campo | Tipo de Dato | Nulo | Descripción |
|---|---|---|---|
| `id` | `INT AUTO_INCREMENT` | NO | Clave primaria identificativa de la reserva. |
| `nombre_huesped` | `VARCHAR(150)` | NO | Nombre y apellidos del huésped titular. |
| `email_huesped` | `VARCHAR(150)` | NO | Correo electrónico de contacto para confirmaciones. |
| `nombre_alojamiento` | `VARCHAR(200)` | NO | Nombre del hotel, casa rural o apartamento. |
| `fecha_entrada` | `DATE` | NO | Fecha de inicio de la estancia (check-in). Formato: `AAAA-MM-DD`. |
| `fecha_salida` | `DATE` | NO | Fecha de fin de la estancia (check-out). Formato: `AAAA-MM-DD`. |
| `estado` | `ENUM('pendiente', 'confirmada', 'cancelada')` | NO | Estado actual de la reserva (por defecto: `pendiente`). |
| `importe` | `DECIMAL(10, 2)` | NO | Importe económico total de la reserva con 2 decimales (ej: `480.50`). |
| `notas` | `TEXT` | SÍ | Observaciones o peticiones especiales del huésped (cuna, mascota, etc.). |
| `fecha_creacion` | `DATETIME` | NO | Fecha y hora exacta de registro en el sistema (por defecto: `CURRENT_TIMESTAMP`). |

---

### 2. Tabla `reserva_evento` (Auditoría e Historial)
Almacena la trazabilidad de cada acción ocurrida sobre una reserva (creación, cambios de estado, cancelaciones):

| Campo | Tipo de Dato | Nulo | Descripción |
|---|---|---|---|
| `id` | `INT AUTO_INCREMENT` | NO | Clave primaria del evento. |
| `reserva_id` | `INT` | NO | Clave foránea que apunta a `reserva(id)`. |
| `tipo` | `VARCHAR(50)` | NO | Tipo de evento (`CREACION`, `CONFIRMACION`, `CANCELACION`). |
| `descripcion` | `TEXT` | NO | Detalle explicativo de lo sucedido (motivo de cancelación, método de pago, notas). |
| `fecha_creacion` | `DATETIME` | NO | Fecha y hora en la que ocurrió el evento. |

---

## ⚡ Índices Estratégicos (Para qué sirven)

En una base de datos pequeña con 10 reservas no se nota diferencia, pero en un software hotelero como MisterPlan con **cientos de miles de reservas al año**, hacer búsquedas sin índices ralentizaría la aplicación por completo.

Por eso hemos creado índices específicos en el script `database.sql`:

1. **`idx_reserva_estado (estado)`**:
   - Acelera de forma instantánea el filtro cuando el usuario selecciona "Ver solo confirmadas" o "Ver solo pendientes".
2. **`idx_reserva_fechas (fecha_entrada, fecha_salida)`**:
   - Acelera los filtros de rango de fechas de estancia.
3. **`idx_reserva_nombre_huesped (nombre_huesped)`**:
   - Permite búsquedas rápidas por el nombre del cliente.
4. **`idx_evento_reserva_id (reserva_id)`**:
   - Permite obtener todos los eventos de una reserva en una fracción de milisegundo al abrir la pantalla de detalle.

---

## 🔗 Integridad Referencial (`FOREIGN KEY`)

La tabla `reserva_evento` tiene una clave foránea hacia `reserva`:
```sql
CONSTRAINT `fk_reserva_evento_reserva`
    FOREIGN KEY (`reserva_id`)
    REFERENCES `reserva` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
```
**¿Qué significa `ON DELETE CASCADE`?**  
Significa que la base de datos se autoprotege: si por alguna razón administrativa se eliminara una reserva, todos sus eventos asociados se borrarían automáticamente, impidiendo que queden registros "huérfanos" que corrompan la integridad del sistema.

---

## 🎭 Datos de Ejemplo Incluidos en `database.sql`

El archivo de base de datos incluye registros representativos del sector turístico en España:
- **Reserva #1 (Confirmada):** *Hotel Rural Mirador de Gredos* (Carlos Jiménez) con notas de cuna para bebé y evento de pago con tarjeta.
- **Reserva #2 (Pendiente):** *Apartamentos Sol & Mar - Torreón B* (Lucía García) pendiente de transferencia bancaria.
- **Reserva #3 (Cancelada):** *Cabañas del Valle de Tena* (Alejandro Morales) cancelada por motivos laborales con evento explicativo en el historial.
- **Reserva #4 (Confirmada):** *Villa Serena Costa Brava* (Elena Santos) pack fin de año con fianza validada.
- **Reserva #5 (Pendiente):** *Casa Rural La Alcarria* (Mateo Fernández) reserva con mascota.

Esto permite que al importar el archivo SQL, quien evalúe la prueba técnica pueda **ver y probar inmediatamente todos los filtros y estados** en acción desde el primer segundo.
