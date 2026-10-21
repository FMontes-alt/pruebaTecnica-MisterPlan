# 02 — Arquitectura del Proyecto y Patrones de Diseño

Si nunca has visto un proyecto PHP estructurado sin framework, esta guía te explica con palabras muy sencillas **cómo está organizada la casa** y **por qué cada archivo está donde está**.

---

## 🏗️ La Estructura de Carpetas

```text
pruebaTecnica-MisterPlan/
│
├── public/                     <- LA PUERTA DE ENTRADA (Única carpeta accesible desde la web)
│   ├── index.php               <- "El Conserje" (Front Controller): recibe todas las visitas
│   └── assets/
│       ├── css/style.css       <- Hoja de estilos (colores, tablas, modales, responsive)
│       └── js/                 <- JavaScript del navegador (peticiones API, filtros, modales)
│
├── src/                        <- EL MOTOR INTERNO (Código PHP de la aplicación)
│   ├── Config/Database.php     <- Conexión con la Base de Datos (PDO)
│   ├── Core/                   <- Herramientas base (Router, Request, Response, View)
│   ├── Controllers/            <- Coordinadores de tráfico (reciben datos y llaman servicios)
│   ├── Services/               <- El "Cerebro" (validaciones, reglas y transacciones)
│   ├── Repositories/           <- El "Almacén" (hace los SELECT, INSERT y UPDATE en SQL)
│   └── Exceptions/             <- Errores controlados (validación, 404, reglas de negocio)
│
├── templates/                  <- LAS PLANTILLAS VISUALES (HTML con Twig)
│   ├── base.html.twig          <- El diseño maestro (cabecera, menú, pie de página)
│   ├── reservas/index.html.twig<- La página principal con la tabla y filtros
│   ├── reservas/detalle.html.twig <- La ficha de una reserva y su historial
│   └── errors/404.html.twig    <- Pantalla de página no encontrada
│
├── tests/                      <- PRUEBAS AUTOMÁTICAS (PHPUnit)
│   └── ReservaServiceTest.php  <- Pruebas de que el cerebro no comete fallos
│
├── database.sql                <- Script para crear la base de datos y datos de prueba
├── composer.json               <- Lista de librerías utilizadas (Twig y PHPUnit)
└── README.md                   <- Documentación principal del repositorio
```

---

## 🔄 El Viaje de una Petición (Cómo fluye la información)

Imagina que un usuario entra en tu web y busca reservas o hace clic en una reserva. ¿Qué pasa por debajo segundo a segundo?

```text
[ 1. Navegador del Usuario ]
           │  (Pide la URL: /reservas/1)
           ▼
[ 2. public/index.php (Front Controller) ]
           │  (Pasa la petición al Router)
           ▼
[ 3. src/Core/Router.php ]
           │  (Identifica qué controlador debe atender esta ruta)
           ▼
[ 4. src/Controllers/ReservaViewController.php ]
           │  (Pide la información de la reserva al Servicio)
           ▼
[ 5. src/Services/ReservaService.php ]  <-- "EL CEREBRO"
           │  (Aplica reglas y pide los datos al Repositorio)
           ▼
[ 6. src/Repositories/ReservaRepository.php ] <-- "EL ALMACÉN"
           │  (Ejecuta la consulta SQL con PDO seguro)
           ▼
[ 7. Base de Datos MySQL ]
           │  (Devuelve la fila de datos)
           ▼
[ 8. src/Core/View.php (Twig) ]
           │  (Inyecta los datos en la plantilla HTML y la protege de ataques)
           ▼
[ 9. Navegador del Usuario ]
   (Muestra la página renderizada y bonita)
```

---

## 🧠 Explicación de las Capas Principales

### 1. El Front Controller (`public/index.php`)
En el PHP antiguo de hace 15 años, la gente creaba archivos como `ver_reserva.php`, `crear_reserva.php`, `eliminar_reserva.php` sueltos por la raíz. Eso era un caos difícil de mantener y un peligro de seguridad.
Hoy en día, las aplicaciones profesionales usan un **Front Controller**: un único archivo (`public/index.php`) que recibe absolutamente todas las peticiones y las reparte ordenadamente.

### 2. El Enrutador (`src/Core/Router.php`)
El router es como una centralita telefónica. Contiene una lista de rutas:
- Si alguien visita por `GET` la ruta `/` -> Llama al método `index` de `ReservaViewController`.
- Si alguien visita por `GET` la ruta `/reservas/{id}` -> Extrae el número `{id}` y llama a `ReservaViewController->show()`.
- Si alguien hace una llamada de datos por `POST` a `/api/reservas` -> Llama a `ReservaApiController->store()`.

### 3. La Capa de Controladores (`src/Controllers/`)
Los controladores son directores de orquesta:
- **No deben tener consultas SQL directas.**
- **No deben tener cálculos matemáticos pesados de negocio.**
- Su trabajo es muy simple: leer lo que pide el usuario (`Request`), llamar al servicio correspondiente para que haga el trabajo duro, y devolver la respuesta (`Response`) en formato HTML o JSON.
- Tenemos dos controladores:
  1. `ReservaViewController`: Genera las páginas web completas en HTML usando Twig.
  2. `ReservaApiController`: Genera datos puros en JSON para que el JavaScript los consuma sin tener que recargar la página entera.

### 4. La Capa de Servicios (`src/Services/ReservaService.php`)
Aquí está la **lógica de negocio** de la empresa MisterPlan. Es el archivo más importante de la aplicación:
- Comprueba si el email es válido.
- Comprueba que la fecha de salida sea posterior a la de entrada.
- Comprueba que el importe sea mayor que cero.
- Controla los estados: si una reserva ya está cancelada, no permite volver a confirmarla ni cancelarla de nuevo.
- **Maneja las Transacciones Atómicas (ACID):** Si se crea una reserva, es obligatorio guardar al mismo tiempo su evento en el historial. Si una de las dos cosas falla, el servicio hace un `rollBack()` (deshacer) y la base de datos queda intacta.

### 5. La Capa de Repositorios (`src/Repositories/`)
Los repositorios son los únicos autorizados para "hablar" con la base de datos:
- `ReservaRepository`: Se encarga de hacer los `SELECT`, `INSERT` y `UPDATE` de la tabla `reserva`.
- `EventoRepository`: Se encarga de insertar y leer el historial de la tabla `reserva_evento`.
- **¿Por qué se separa de esta forma?** Porque si el día de mañana MisterPlan decide cambiar MySQL por PostgreSQL, MongoDB o una API externa, la lógica de negocio (`ReservaService`) no se toca en absoluto; solo se cambia el repositorio.

---

## 🧩 Patrones de Diseño Utilizados

Si te preguntan en la entrevista qué patrones de diseño has usado en la prueba, puedes responder con total seguridad:

1. **Patrón MVC (Modelo - Vista - Controlador):** Separación de la presentación visual (Vistas Twig), la coordinación (Controladores) y los datos y reglas (Modelo y Servicios).
2. **Patrón Front Controller:** Un único punto de entrada (`public/index.php`) que centraliza el arranque, la carga de configuración y el despacho de rutas.
3. **Patrón Repositorio (Repository Pattern):** Aísla por completo las consultas SQL del resto del código.
4. **Capa de Servicio (Service Layer):** Centraliza las reglas de negocio y las transacciones de base de datos fuera de los controladores.
5. **Patrón Singleton:** En la clase `Database.php`, para que exista una única conexión PDO compartida y no abrir decenas de conexiones innecesarias que saturen el servidor.
6. **Inyección de Dependencias:** El `ReservaService` recibe sus repositorios por el constructor, lo que permite que en los tests unitarios (`tests/ReservaServiceTest.php`) podamos pasarle "dobles" o "mocks" sin necesidad de tocar la base de datos real.
