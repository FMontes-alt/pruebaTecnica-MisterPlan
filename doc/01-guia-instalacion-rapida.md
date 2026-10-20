# 01 — Guía de Instalación y Puesta en Marcha Rápida

Esta guía está pensada para que **cualquier persona**, incluso sin experiencia previa en este proyecto, pueda descargar la aplicación, levantar la base de datos y tenerla funcionando en su navegador en **menos de 5 minutos**.

---

## 🛠️ Requisitos Mínimos

Para ejecutar este proyecto en tu ordenador necesitas:

1. **PHP (versión 8.1 o superior):**
   - Viene incluido de serie si tienes instalado **XAMPP**, **WAMP** o **MAMP**.
   - Para comprobar tu versión, abre una consola de comandos (PowerShell o CMD) y escribe:
     ```bash
     php -v
     ```
2. **MySQL o MariaDB:**
   - Viene incluido en el panel de control de XAMPP/WAMP.
3. **Composer:**
   - Es el gestor oficial de librerías de PHP (se usa para descargar Twig y PHPUnit).
   - Para comprobar si lo tienes instalado:
     ```bash
     composer -v
     ```
4. **Git:**
   - Para clonar el repositorio o gestionar el control de versiones.

---

## 🚀 Paso a Paso Desde Cero

### Paso 1: Abrir la carpeta del proyecto
Abre tu terminal en la carpeta raíz del proyecto:
```bash
cd c:\Users\Fran\Desktop\Prueba-Tecnica-Fco-Montes
```

---

### Paso 2: Instalar las librerías con Composer
Ejecuta en la consola:
```bash
composer install
```
**¿Qué hace este comando?**
- Lee el archivo `composer.json`.
- Descarga automáticamente en la carpeta `vendor/` el motor de plantillas **Twig** y la herramienta de tests **PHPUnit**.
- Genera el archivo `vendor/autoload.php` (el autocargador de clases PSR-4), que permite que PHP encuentre y use todas las clases de la aplicación sin tener que hacer cientos de `require_once` manuales.

---

### Paso 3: Asegurarte de que MySQL está encendido
- Si usas **XAMPP**: Abre el *XAMPP Control Panel* y pulsa en el botón **Start** al lado de **MySQL** (y de **Apache** si deseas usar phpMyAdmin).
- Debe ponerse en verde indicando el puerto `3306`.

---

### Paso 4: Crear la Base de Datos e Importar los Datos
El proyecto incluye un archivo llamado `database.sql` en la raíz. Este archivo contiene la creación de las tablas y registros de prueba realistas.

#### Opción Rápida por Terminal (Recomendada):
Si tienes MySQL en tu PATH de Windows:
```bash
mysql -u root -p < database.sql
```
*(Si no tienes contraseña configurada en tu MySQL local, simplemente pulsa la tecla ENTER).*

O ejecutando el binario directo de XAMPP:
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "source database.sql"
```

#### Opción Visual con phpMyAdmin:
1. Abre tu navegador y ve a: `http://localhost/phpmyadmin/`
2. En el menú superior haz clic en **Importar**.
3. Pulsa en **Seleccionar archivo** y elige `database.sql` de la carpeta del proyecto.
4. Baja hasta el final y haz clic en el botón **Importar**.
5. ¡Listo! Se habrá creado la base de datos `misterplan_reservas` con sus dos tablas (`reserva` y `reserva_evento`).

---

### Paso 5: Arrancar el Servidor Web de Desarrollo
No necesitas configurar servidores complejos como Apache o Nginx para probarlo en local. PHP incluye su propio servidor web interno.

Ejecuta en tu terminal:
```bash
php -S localhost:8000 -t public
```

> **¡MUY IMPORTANTE!**  
> Fíjate en el parámetro `-t public`. Le indica a PHP que la carpeta raíz pública es `public/`. Esto es una **regla de oro de seguridad**, porque así nadie desde internet puede ver ni acceder a tus archivos de configuración, código fuente en `src/` o plantillas en `templates/`.

---

### Paso 6: Abrir la Aplicación en tu Navegador
Abre Google Chrome, Firefox o Edge y visita:
👉 **[http://localhost:8000](http://localhost:8000)**

Verás la pantalla de inicio con la lista de reservas, los filtros dinámicos, el botón de crear reserva y el acceso al detalle de cada una.

---

## 🧪 Cómo Ejecutar los Tests Unitarios
Para comprobar que toda la lógica de negocio y validaciones funcionan al 100%:
```bash
vendor/bin/phpunit
```
o también:
```bash
composer test
```
Deberás ver una salida verde con **OK (7 tests, 9 assertions)**.
