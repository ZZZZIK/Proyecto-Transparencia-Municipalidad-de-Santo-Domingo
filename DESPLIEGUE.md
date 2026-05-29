# Guía de Despliegue — Portal Transparencia Santo Domingo

Esta guía explica paso a paso cómo desplegar el proyecto y conectar la base de datos en su servidor web (producción) utilizando **phpMyAdmin** y **FTP/cPanel**, sin necesidad de ejecutar comandos por consola en el servidor.

---

## 1. Estructura de la Aplicación

El proyecto se encuentra unificado bajo la estructura estándar de **Laravel**:
* **`/public`**: Contiene la interfaz de usuario (frontend de alta fidelidad: HTML, CSS, JS, imágenes). El servidor web debe apuntar a esta carpeta como directorio raíz público.
* **`/app`, `/routes`, `/config`**: Contiene la lógica del backend dinámico en PHP.
* **`database/schema.sql`**: Script consolidado para importar en **phpMyAdmin**.

> [!TIP]
> **Compatibilidad Local Automática (Graceful Fallback)**:
> Si abres los archivos estáticos en tu computador sin tener un servidor PHP/MySQL corriendo, el frontend cargará automáticamente los archivos JSON estáticos de respaldo (`public/data/`). Al subirlo al servidor web configurado con base de datos, el sitio comenzará a consumir los datos reales de la API automáticamente sin cambiar una sola línea de código.

---

## 2. Configuración en Servidor de Producción (cPanel / Hosting / FTP)

### Paso A: Importar la Base de Datos en phpMyAdmin
1. Ingrese al panel de control de su hosting (ej. cPanel).
2. Abra **phpMyAdmin**.
3. En la barra lateral izquierda, cree o seleccione la base de datos destinada para el portal (ejemplo: `transparencia_santo_domingo`).
4. Haga clic en la pestaña **"Importar"** (en el menú superior).
5. Seleccione el archivo **`database/schema.sql`** ubicado en el proyecto.
6. Deje las opciones predeterminadas y haga clic en **"Importar"** (o **"Continuar"**).
   * *¡Listo! Las tablas de metadata, recaudación, proyectos, servicios y el perfil encriptado del contribuyente semilla se habrán creado y poblado.*

### Paso B: Subir los Archivos vía FTP o Administrador de Archivos
1. Conéctese a su servidor mediante un cliente FTP (como FileZilla) o abra el **Administrador de Archivos** de su cPanel.
2. Suba todos los archivos de este proyecto al directorio de su servidor.
3. **Punto clave de Laravel**: El dominio o subdominio asignado al portal de transparencia debe estar configurado para apuntar a la carpeta **`public/`** del proyecto como raíz web (Document Root).

### Paso C: Configurar las Credenciales en el Servidor (`.env`)
1. En el Administrador de Archivos de su servidor, busque el archivo **`.env`** en la carpeta raíz del proyecto (si no es visible, active "Mostrar archivos ocultos" o renómbrelo desde `.env.example`).
2. Edite el archivo `.env` y configure los datos de conexión a su base de datos de producción:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://transparencia.santodomingo.cl   # URL de su portal

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_su_base_de_datos
DB_USERNAME=usuario_de_su_base_de_datos
DB_PASSWORD=contraseña_de_su_base_de_datos
```
3. Guarde los cambios. El portal de transparencia ya estará operando de forma 100% dinámica conectado a su base de datos MySQL.

---

## 3. ¿Cómo probarlo en mi Computador Local?

Si en el futuro desea ejecutar el backend de forma local en su computador, necesitará instalar un entorno de desarrollo PHP y MySQL. A continuación le explicamos cómo hacerlo de forma rápida en Windows:

### Método Recomendado: Instalar Laragon o XAMPP
1. Descargue e instale **[Laragon](https://laragon.org/download/)** (Recomendado, es ligero y autoconfigura todo) o **[XAMPP](https://www.apachefriends.org/)**.
2. **Si usa Laragon**:
   * Ábralo y presione **"Iniciar todo"**.
   * Haga clic derecho en Laragon -> **Quick App** -> **Blank** y seleccione la carpeta de este proyecto, o simplemente arrastre la carpeta del proyecto a `C:\laragon\www\`.
   * Laragon creará un dominio local automático (ej. `http://proyecto.test`) donde podrá probarlo con base de datos en tiempo real.
   * Abra phpMyAdmin o Database (Laragon tiene botón "Database" con HeidiSQL integrado) e importe el archivo `database/schema.sql`.
3. **Si usa XAMPP**:
   * Inicie los servicios de **Apache** y **MySQL** desde el Panel de Control de XAMPP.
   * Coloque la carpeta del proyecto dentro de `C:\xampp\htdocs\`.
   * Ingrese a `http://localhost/phpmyadmin` en su navegador, cree una base de datos e importe `database/schema.sql`.

### Instalar Composer (Solo necesario si desea modificar el código PHP y reconstruir dependencias)
1. Descargue e instale el instalador para Windows de **[Composer](https://getcomposer.org/Composer-Setup.exe)**.
2. Abra una consola de comandos (PowerShell) en la raíz del proyecto y ejecute:
```bash
composer install
php artisan key:generate
```

---

## 4. Cumplimiento de Políticas de Privacidad (Normativa ISO)

Este backend implementa medidas proactivas de seguridad e intimidad de datos:
1. **Encriptación**: Los campos `rut_encriptado` y `nombre_encriptado` de los ciudadanos se almacenan utilizando cifrado robusto de grado militar (AES-256-CBC) a través de Laravel.
2. **Búsqueda por Hash no Reversible**: Para validar la ClaveÚnica simulada, el controlador genera un hash SHA-256 del RUT del usuario (`rut_hash`) y lo compara contra la base de datos. De esta forma, el RUT original de los usuarios **nunca** se consulta en texto plano.
3. **Defensa contra Raspado**: Las rutas dinámicas de la API están protegidas mediante el middleware `throttle`, limitando las consultas a un máximo de 60 por minuto para evitar que robots descarguen masivamente la información salarial o presupuestaria del personal.
