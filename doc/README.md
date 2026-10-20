# 📚 Índice de Documentación del Proyecto

Bienvenido/a a la documentación técnica del sistema de gestión de reservas turísticas para **MisterPlan**.

Esta serie de guías ha sido elaborada para explicar en profundidad, con lenguaje claro y accesible, cada decisión de arquitectura, regla de negocio, medida de seguridad y detalle de implementación.

---

## 🗂️ Contenido de las Guías

1. **[01 — Guía de Instalación y Puesta en Marcha Rápida](01-guia-instalacion-rapida.md)**  
   Requisitos, importación de la base de datos `database.sql`, arranque del servidor local con `php -S` y ejecución de tests.

2. **[02 — Arquitectura del Proyecto y Patrones de Diseño](02-arquitectura-y-patrones.md)**  
   Estructura de carpetas, el flujo de una petición (Front Controller -> Router -> Controlador -> Servicio -> Repositorio -> BD) y patrones utilizados (MVC, Repository, Service Layer, Singleton, DI).

3. **[03 — Base de Datos y Modelo de Datos](03-base-de-datos-y-modelo.md)**  
   Estructura de las tablas `reserva` y `reserva_evento`, relaciones con claves foráneas en cascada, índices de rendimiento y datos de prueba realistas.

4. **[04 — Lógica de Negocio, Reglas y Transacciones](04-logica-de-negocio-y-reglas.md)**  
   La máquina de estados de las reservas, validaciones exhaustivas en servidor, transacciones atómicas con PDO (`beginTransaction`, `commit`, `rollBack`) y excepciones específicas.

5. **[05 — Vistas con Twig y Frontend Interactivo](05-vistas-twig-y-frontend.md)**  
   Herencia de plantillas en Twig, auto-escaping de seguridad, filtros de fecha/moneda y el JavaScript Vanilla que consume la API con Fetch y debounce sin recargar la página.

6. **[06 — Seguridad y Buenas Prácticas Aplicadas](06-seguridad-y-buenas-practicas.md)**  
   Blindaje contra inyección SQL con sentencias preparadas, protección frente a XSS, aislamiento del Document Root `/public` y control de errores.

7. **[07 — Guía para Defender la Prueba en la Entrevista](07-guia-para-la-entrevista.md)**  
   Preguntas técnicas frecuentes que te harán los evaluadores de MisterPlan y cómo responder a cada una de forma brillante y con criterio.
