# Sistema de Gestión de Reservas de Alojamiento — MisterPlan

> **Prueba Técnica para Desarrollador/a Junior PHP**  
> **Candidato:** Francisco Montes  
> **Tecnologías:** PHP 8.2 (Nativo, sin framework) &bull; Twig 3.x &bull; MySQL / MariaDB (PDO) &bull; Vanilla JavaScript &bull; CSS3 &bull; PHPUnit 10.x

---

## 📋 Descripción del Proyecto

Aplicación web integral para la gestión y auditoría de reservas de alojamiento turístico. Diseñada con una arquitectura limpia en **PHP nativo**, separando estrictamente la capa de presentación, la lógica de negocio y el acceso a datos.

La aplicación cuenta con:
1. **API REST en PHP nativo:** Endpoints JSON para consultar, filtrar, crear y actualizar reservas con códigos de estado HTTP semánticos (200, 201, 400, 404, 422).
2. **Vistas renderizadas en servidor con Twig:** Plantilla base con herencia (`{% extends %}`), auto-escaping de seguridad y formato de moneda y fechas.
3. **Frontend interactivo con JavaScript Vanilla:** Filtrado dinámico sin recargar la página, formulario modal para altas con validación en cliente/servidor, cancelación con modal de confirmación y timeline en tiempo real.
4. **Historial de eventos y auditoría:** Registro atómico de cada cambio de estado mediante transacciones SQL (ACID).
5. **Suite de pruebas unitarias:** Tests con PHPUnit que verifican las reglas de negocio y restricciones de dominio.

---

## 📚 Documentación Técnica Detallada (`/doc`)

Para un análisis exhaustivo y didáctico de cada parte del sistema, dispones de 6 guías dedicadas:
1. **[01 — Guía de Instalación Rápida](doc/01-guia-instalacion-rapida.md)**: Puesta en marcha paso a paso en menos de 5 minutos.
2. **[02 — Arquitectura y Patrones](doc/02-arquitectura-y-patrones.md)**: Flujo de peticiones, capas y patrones (MVC, Repository, Service Layer).
3. **[03 — Base de Datos y Modelo](doc/03-base-de-datos-y-modelo.md)**: Tablas, claves foráneas, índices de rendimiento y datos de prueba.
4. **[04 — Lógica de Negocio y Reglas](doc/04-logica-de-negocio-y-reglas.md)**: Máquina de estados, validaciones en servidor y transacciones ACID.
5. **[05 — Vistas Twig y Frontend](doc/05-vistas-twig-y-frontend.md)**: Herencia de plantillas, auto-escaping y JS Vanilla asíncrono con debounce.
6. **[06 — Seguridad y Buenas Prácticas](doc/06-seguridad-y-buenas-practicas.md)**: Blindaje SQL, prevención de XSS, aislamiento de Document Root y manejo de errores.

---

## 🚀 Puesta en Marcha en Local

### Requisitos previos
- **PHP 8.1 o superior** (probado y verificado en PHP 8.2) con extensiones `pdo_mysql` y `mbstring`.
- **MySQL / MariaDB** (disponible en XAMPP, WAMP, MAMP o Docker).
- **Composer** (para descargar Twig y PHPUnit).

---

### Paso 1: Clonar el repositorio
```bash
git clone <url-del-repositorio>
cd pruebaTecnica-MisterPlan
```

### Paso 2: Instalar dependencias
```bash
composer install
```

### Paso 3: Crear y poblar la Base de Datos
El proyecto incluye el archivo `database.sql` con el DDL completo, índices optimizados y datos de prueba realistas (hoteles rurales, campings y apartamentos con reservas en estados pendiente, confirmada y cancelada).

**Opción A: Por línea de comandos**
```bash
mysql -u root -p < database.sql
```
*(Si no tienes contraseña configurada en MySQL local, pulsa Enter cuando te la solicite).*

**Opción B: Vía phpMyAdmin (XAMPP / MAMP)**
1. Abre `http://localhost/phpmyadmin/`.
2. Ve a la pestaña **Importar**.
3. Selecciona el archivo `database.sql` de la raíz del proyecto y haz clic en **Importar**.

---

### Paso 4: Configuración de conexión (Opcional)
Por defecto, la conexión conecta a:
- **Host:** `localhost`
- **Puerto:** `3306`
- **Base de datos:** `misterplan_reservas`
- **Usuario:** `root`
- **Contraseña:** `""` (vacía)

Si tu entorno requiere credenciales diferentes, puedes definirlas mediante variables de entorno en el sistema o al ejecutar el servidor:
```bash
# Ejemplo en Linux / macOS:
export DB_HOST=localhost DB_NAME=misterplan_reservas DB_USER=root DB_PASS=secreto

# Ejemplo en Windows PowerShell:
$env:DB_HOST="localhost"; $env:DB_NAME="misterplan_reservas"; $env:DB_USER="root"; $env:DB_PASS=""
```

---

### Paso 5: Arrancar el servidor web de desarrollo
Ejecuta el servidor web integrado de PHP apuntando a la carpeta pública:
```bash
php -S localhost:8000 -t public
```

Abre en tu navegador:
👉 **http://localhost:8000**

---

### Paso 6: Ejecución de Tests con PHPUnit
Para verificar la suite de tests unitarios y de reglas de negocio:
```bash
vendor/bin/phpunit
```
o mediante el script de composer:
```bash
composer test
```

---

## 🏛️ Decisiones de Arquitectura y Diseño

### 1. ¿Por qué prescindir de un framework?
El objetivo de la prueba es evaluar el dominio de los fundamentos del lenguaje y los patrones de diseño subyacentes. Se ha implementado una arquitectura basada en:
- **Front Controller (`public/index.php`):** Único punto de entrada seguro. Las carpetas internas (`src/`, `templates/`, `tests/`) quedan fuera del Document Root web.
- **Router nativo (`src/Core/Router.php`):** Permite enrutamiento REST con parámetros dinámicos (`/reservas/{id}`) y manejo unificado de errores 404, 405 y 500.
- **Inversión de dependencias y desacoplamiento:** Los servicios no están acoplados directamente al framework ni a consultas globales.

### 2. Separación de Responsabilidades (SoC)
El código se organiza en tres capas principales:
- **Capa de Persistencia (`src/Repositories/`):**
  - `ReservaRepository`: Consultas SQL con sentencias preparadas y filtros combinables dinámicos.
  - `EventoRepository`: Inserción y consulta del historial de auditoría de la reserva.
- **Capa de Dominio y Negocio (`src/Services/`):**
  - `ReservaService`: Contiene las validaciones (fechas, emails, importes) y orquesta las **transacciones atómicas** (`beginTransaction`, `commit`, `rollBack`). Si falla el guardado del evento, la reserva no se crea ni se altera, garantizando la consistencia de datos (ACID).
- **Capa de Presentación y Controladores (`src/Controllers/`):**
  - `ReservaViewController`: Renderiza las vistas HTML mediante Twig.
  - `ReservaApiController`: Expone los endpoints JSON y maneja códigos HTTP acordes al estándar REST.

### 3. Elección de la Base de Datos (MySQL / MariaDB)
- **Consistencia relacional:** Las reservas hoteleras requieren integridad referencial estricta (`FOREIGN KEY` con `ON DELETE CASCADE`) y consistencia en el estado contable y de ocupación.
- **Índices estratégicos:** Se han añadido índices en `reserva` (`idx_reserva_estado`, `idx_reserva_fechas`, `idx_reserva_nombre_huesped`) para garantizar respuestas sub-milisegundo en las consultas con filtros combinados.

### 4. Vistas con Twig y Seguridad
- **Herencia de plantillas:** `templates/base.html.twig` centraliza el esqueleto HTML, meta tags y assets. Las vistas hijas (`index.html.twig`, `detalle.html.twig`) solo implementan los bloques de contenido.
- **Auto-escaping activado:** Twig escapa de manera automática cualquier dato interpolado (`{{ variable }}`). **No se ha utilizado la etiqueta `|raw`**, garantizando protección absoluta frente a ataques XSS.
- **Filtros nativos:** Se aprovechan filtros como `|date('d/m/Y')` y `|number_format(2, ',', '.')` directamente en las plantillas.

---

## 🔒 Seguridad Implementada

1. **Blindaje contra SQL Injection:** 100% de las consultas utilizan sentencias preparadas (`PDO::prepare`) con vinculación de parámetros. Además, se desactiva la emulación (`PDO::ATTR_EMULATE_PREPARES => false`) para asegurar sentencias reales en el protocolo del motor MySQL.
2. **Mitigación de Cross-Site Scripting (XSS):** Auto-escaping estricto en Twig y sanitización de texto con `escapeHtml()` en el DOM de JavaScript al pintar filas dinámicas.
3. **Validación Exhaustiva en Servidor:**
   - Fechas con formato válido `AAAA-MM-DD`.
   - Fecha de salida estrictamente posterior a la fecha de entrada.
   - Email validado mediante `filter_var(..., FILTER_VALIDATE_EMAIL)`.
   - Importe numérico positivo mayor a 0.
   - Control de máquina de estados: no se permite alterar una reserva en estado `cancelada`.
4. **Aislamiento del Document Root:** Solo la carpeta `public/` es accesible desde la web.

---

## 📡 Referencia de la API REST

| Método | Endpoint | Descripción | Códigos HTTP |
|---|---|---|---|
| `GET` | `/api/reservas` | Listar reservas con filtros combinados (`?estado=...&search=...&fecha_desde=...&fecha_hasta=...`) | `200 OK` |
| `GET` | `/api/reservas/{id}` | Obtener detalle de una reserva y su historial de eventos | `200 OK`, `404 Not Found` |
| `POST` | `/api/reservas` | Crear una nueva reserva | `201 Created`, `422 Unprocessable Content` |
| `PATCH` | `/api/reservas/{id}/estado` | Cambiar estado (`confirmada` o `cancelada`) con motivo opcional | `200 OK`, `400 Bad Request`, `404 Not Found` |

---

## ⏱️ Qué se haría diferente con más tiempo

1. **Gestión de Disponibilidad e Inventario Real:**
   - Añadir una tabla de `habitacion` o `tipo_unidad` para comprobar el solapamiento de fechas y prevenir overbooking a nivel de base de datos (`SELECT ... FOR UPDATE`).
2. **Contenedorización Docker:**
   - Incluir un `Dockerfile` y `docker-compose.yml` (PHP-FPM + Nginx + MariaDB) para levantar el entorno completo con un comando `docker compose up -d`.
3. **Paginación Asíncrona:**
   - Paginación server-side (offset/cursor) en el listado para optimizar el rendimiento con catálogos de decenas de miles de reservas.
4. **Autenticación y Roles:**
   - Sistema de usuarios (Recepcionista, Administrador) con autenticación basada en sesiones seguras o tokens JWT para auditar quién realizó cada cambio de estado.
5. **Análisis Estático de Código:**
   - Integración de PHPStan (nivel 8/9) y PHP CS Fixer (estándar PSR-12) en una pipeline de GitHub Actions.

---

## 📈 Análisis de Escalabilidad (1.000 reservas / hora)

Si esta aplicación pasara de gestionar unas decenas de reservas al día a un flujo turístico de alta concurrencia (1.000 reservas por hora con picos de consultas y búsquedas):

### 1. Concurrencia y Prevención de Overbooking
- **Locking Pesimista vs Optimista:** En el momento crítico del checkout, aplicar locking pesimista (`SELECT id FROM habitacion WHERE id = ? FOR UPDATE`) dentro de una transacción corta, o locking optimista mediante una columna `version` para evitar condiciones de carrera cuando dos huéspedes intentan reservar la última habitación al mismo segundo.

### 2. Desacoplamiento Asíncrono (Message Broker / Colas)
- Actualmente la creación y los eventos se graban en la misma llamada síncrona. Con 1.000 reservas/hora, operaciones secundarias como:
  - Envío de emails de confirmación al huésped y al hotelero.
  - Sincronización con OTAs (Booking, Expedia) vía Channel Manager.
  - Notificaciones webhook.
  deben delegarse a trabajadores asíncronos mediante una cola de mensajería como **RabbitMQ**, **Redis Streams** o **Amazon SQS**.

### 3. Arquitectura de Base de Datos
- **Separación Lectura / Escritura (Read Replicas):** Las consultas de búsqueda de disponibilidad y listados de reservas se dirigen a réplicas de lectura (Read Replicas), liberando al nodo Maestro (Primary) para procesar únicamente las transacciones de escritura.
- **Capa de Caché en Memoria (Redis):** Cachear los resultados de disponibilidad y metadatos de alojamientos con TTL corto e invalidación controlada por eventos.

### 4. Servidor y Red
- **Reverse Proxy y Rate Limiting:** Uso de Nginx y Cloudflare para balanceo de carga, compresión gzip/brotli, terminación SSL y mitigación de ataques o bots de scraping de precios.
- **PHP OPcache:** Activar y optimizar OPcache (`opcache.enable=1`, `opcache.memory_consumption=256`) y precarga en producción para evitar recompilar los ficheros PHP en cada petición.

---

## 🌐 Despliegue en Producción

El proyecto está preparado para desplegarse fácilmente en cualquier hosting PHP o plataforma Cloud (Render, Railway, Fly.io, Heroku, hosting compartido):

1. **Variables de entorno para producción:**
   - `DB_HOST`: Host del servidor MySQL remoto.
   - `DB_PORT`: Puerto (habitualmente `3306`).
   - `DB_NAME`: Nombre de la base de datos.
   - `DB_USER`: Usuario con permisos.
   - `DB_PASS`: Contraseña de la base de datos.
2. **Document Root:** Configurar el VirtualHost de Apache/Nginx apuntando a la carpeta `/public`.
3. **URL del Deploy:** `[Añadir aquí la URL del hosting gratuito elegido una vez subido a tu repositorio]`
