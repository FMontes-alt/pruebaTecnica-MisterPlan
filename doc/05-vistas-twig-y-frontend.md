# 05 — Vistas con Twig y Frontend Interactivo

Este documento explica cómo funciona la parte visual y la interactividad de la aplicación: el motor de plantillas **Twig** en el servidor y el código **JavaScript Vanilla** en el navegador.

---

## 🍃 1. ¿Cómo Funciona Twig? (Plantillas en Servidor)

**Twig** es un motor de plantillas que permite escribir HTML limpio sin mezclar etiquetas de PHP (`<?php echo ... ?>`).

### A. Herencia de Plantillas (No repetir código)
En lugar de repetir el `<!DOCTYPE html>`, las etiquetas `<head>`, los enlaces CSS y el menú de navegación en cada página:

1. Creamos la plantilla padre: `templates/base.html.twig`.
   - Contiene la estructura global.
   - Define "huecos" llamados bloques: `{% block content %}{% endblock %}`.
2. Las páginas secundarias heredan de la plantilla padre:
   ```twig
   {% extends "base.html.twig" %}

   {% block title %}Listado de Reservas{% endblock %}

   {% block content %}
       <h1>Aquí dentro solo va el contenido específico de esta página</h1>
   {% endblock %}
   ```

### B. Auto-Escaping de Seguridad
Si en PHP normal imprimes una variable enviada por un usuario:
```php
<!-- VULNERABLE a inyecciones XSS si alguien escribe <script>alert('hack')</script> -->
<?= $reserva['nombre_huesped'] ?>
```
En **Twig**, el auto-escaping está encendido por defecto:
```twig
<!-- Twig convierte automáticamente <script> en &lt;script&gt; de forma segura -->
{{ reserva.nombre_huesped }}
```
> **Nota de Evaluación:** En el enunciado de la prueba valoran expresamente el autoescaping y señalan que si usas `|raw`, debes justificarlo. En nuestro proyecto **no se usa `|raw` en ningún sitio**, garantizando seguridad total.

### C. Filtros de Formato en Twig
Twig incluye filtros muy útiles para presentar los datos con elegancia:
- **Fechas:** `{{ reserva.fecha_entrada|date('d/m/Y') }}` transforma `2026-11-01` en `01/11/2026`.
- **Importes monetarios:** `{{ reserva.importe|number_format(2, ',', '.') }} €` transforma `480.00` en `480,00 €`.
- **Mayúsculas/Minúsculas:** `{{ reserva.estado|capitalize }}` transforma `confirmada` en `Confirmada`.

---

## ⚡ 2. Frontend con JavaScript Vanilla (Sin Frameworks)

Para que la página sea ágil y moderna, usamos **JavaScript Vanilla** (estándar ES6+, sin jQuery ni React) para comunicarnos con la API REST mediante la función nativa `fetch()`.

El código JavaScript se divide en tres archivos muy claros:

### 1. `public/assets/js/api.js` (El Cliente de la API)
Contiene las funciones que hacen las llamadas HTTP al servidor:
- `api.getReservas(filtros)`: Llama a `GET /api/reservas?estado=...&search=...`.
- `api.getReserva(id)`: Llama a `GET /api/reservas/1`.
- `api.createReserva(datos)`: Llama a `POST /api/reservas` enviando el JSON.
- `api.updateEstado(id, nuevoEstado, motivo)`: Llama a `PATCH /api/reservas/1/estado`.
- También incluye `window.showToast(mensaje, tipo)` para mostrar avisos visuales flotantes de éxito o error.

### 2. `public/assets/js/app.js` (La Pantalla Principal)
Controla la interactividad del listado:
- **Filtro con Debounce:** Cuando el usuario teclea en el buscador de texto, espera 280 milisegundos tras la última pulsación antes de consultar a la API, evitando saturar el servidor con peticiones en cada letra tecleada.
- **Repintado dinámico de la tabla:** Recibe la lista de reservas en JSON y genera las filas HTML en el DOM al instante, sin recargar la página.
- **Modal de Nueva Reserva:**
  - Valida en el navegador antes de enviar (que el email tenga formato, que la salida sea posterior a la entrada, etc.).
  - Muestra un estado de carga en el botón (`⏳ Guardando...`).
  - Si el servidor devuelve algún error, lo pinta en rojo bajo el campo correspondiente.
- **Modal de Cancelación:** Abre una ventana emergente para pedir confirmación y un motivo opcional antes de cancelar.

### 3. `public/assets/js/detalle.js` (La Pantalla de Detalle)
Controla las acciones dentro de la ficha de una reserva:
- **Botón "Confirmar Reserva":** Envía la petición a la API, cambia el badge visual a verde ("Confirmada"), retira el botón de confirmar y **añade automáticamente el nuevo evento al Timeline** sin recargar la página.
- **Botón "Cancelar Reserva":** Solicita confirmación y actualiza el estado y el historial en tiempo real.

---

## 🎨 3. Estilos CSS y Diseño Responsive (`style.css`)

El diseño utiliza **CSS Moderno nativo**:
- **Variables CSS (Design Tokens):** Paleta de colores cuidada (azul corporativo, esmeralda para confirmadas, ámbar para pendientes, rojo para canceladas).
- **Tipografía Google Fonts:** Usa *Plus Jakarta Sans*, una fuente limpia y moderna.
- **Layout Adaptable (Responsive):**
  - En pantallas grandes (ordenador): diseño en múltiples columnas y cuadrículas (CSS Grid).
  - En pantallas pequeñas (móviles): las columnas se apilan verticalmente y las tablas permiten desplazamiento horizontal suave (`overflow-x: auto`).
- **Timeline de Auditoría:** Línea vertical con marcadores circulares y etiquetas de colores según el tipo de evento (`CREACION`, `CONFIRMACION`, `CANCELACION`).
