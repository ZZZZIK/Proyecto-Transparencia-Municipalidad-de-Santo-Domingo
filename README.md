# Guía de Despliegue Final — Portal de Transparencia Activa
## I. Municipalidad de Santo Domingo

**Instrucciones completas para ejecutar y testear el proyecto en un computador local con Windows.**

---

## Tabla de Contenidos

1. [Requisitos Previos (Software Necesario)](#1-requisitos-previos-software-necesario)
2. [Instalación Paso a Paso](#2-instalación-paso-a-paso)
3. [Iniciar el Servidor Local](#3-iniciar-el-servidor-local)
4. [Credenciales de Prueba](#4-credenciales-de-prueba)
5. [Funcionalidades Disponibles para Testeo](#5-funcionalidades-disponibles-para-testeo)
6. [Estructura del Proyecto](#6-estructura-del-proyecto)
7. [Notas Técnicas Importantes](#7-notas-técnicas-importantes)
8. [Solución de Problemas Frecuentes](#8-solución-de-problemas-frecuentes)

---

## 1. Requisitos Previos (Software Necesario)

Antes de comenzar, asegúrese de tener instalados los siguientes programas en su computador:

| # | Programa | Versión Mínima | Descripción | Descarga |
|---|----------|---------------|-------------|----------|
| 1 | **PHP** | 8.1 o superior | Motor de ejecución del backend Laravel | [php.net/downloads](https://www.php.net/downloads) |
| 2 | **Composer** | 2.x | Gestor de dependencias y librerías de PHP | [getcomposer.org](https://getcomposer.org/Composer-Setup.exe) |

### Método Recomendado: Instalación Rápida con Laragon (Todo-en-Uno)

Si no desea instalar PHP y Composer por separado, la forma más sencilla y rápida es usar **Laragon**, que los incluye ambos preconfigurados y listos para usar:

1. Descargue **[Laragon Full](https://laragon.org/download/)** e instálelo con las opciones por defecto.
2. Una vez instalado, ábralo y presione **"Iniciar todo"** (Start All).
3. Laragon ya incluye PHP 8.x con todas las extensiones necesarias habilitadas y Composer instalado globalmente. No necesita configurar nada más.

### Método Alternativo: Instalación con XAMPP

1. Descargue e instale **[XAMPP](https://www.apachefriends.org/)** (versión con PHP 8.1 o superior).
2. Inicie el servicio de **Apache** desde el Panel de Control de XAMPP.
3. Instale **[Composer](https://getcomposer.org/Composer-Setup.exe)** de forma independiente ejecutando el instalador para Windows.

### Método Alternativo: Instalación con winget (Línea de Comandos)

Si tiene **winget** disponible (viene incluido en Windows 10/11), puede instalar PHP directamente desde PowerShell:

```powershell
winget install PHP.PHP.8.2
```

Luego descargue Composer desde [getcomposer.org](https://getcomposer.org/Composer-Setup.exe) y ejecute el instalador.

### Verificar que todo está instalado correctamente

Abra una terminal (PowerShell o CMD) y ejecute los siguientes comandos. Si ambos responden con una versión, está listo para continuar:

```powershell
php -v
```
> Debe mostrar algo como: `PHP 8.2.x (cli)`

```powershell
composer --version
```
> Debe mostrar algo como: `Composer version 2.x.x`

### Extensiones PHP Requeridas

PHP debe tener habilitadas las siguientes extensiones en su archivo `php.ini`. **Si usó Laragon o XAMPP, estas ya vienen habilitadas por defecto** y no necesita hacer nada. Si instaló PHP manualmente, abra el archivo `php.ini` (ubicado en la carpeta de instalación de PHP) y descomente (quite el `;` del inicio) las siguientes líneas:

```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=mysqli
extension=pdo_mysql
extension=zip
```

---

## 2. Instalación Paso a Paso

Una vez que tiene PHP y Composer instalados, siga estos pasos en orden:

### Paso 1: Abrir una terminal en la carpeta del proyecto

Abra **PowerShell** o **CMD** y navegue hasta la carpeta raíz del proyecto:

```powershell
cd C:\ruta\donde\descomprimió\el\proyecto
```

> **Ejemplo**: Si descomprimió el proyecto en el Escritorio, sería:
> ```powershell
> cd C:\Users\SuUsuario\Desktop\municipalidad
> ```

### Paso 2: Instalar las dependencias del framework Laravel

Ejecute el siguiente comando para descargar e instalar todas las librerías necesarias del framework:

```powershell
composer install
```

> ⏱️ **Tiempo estimado**: Entre 1 y 3 minutos dependiendo de la velocidad de su conexión a Internet.
> Este comando descargará las 110 librerías del framework Laravel y las almacenará en la carpeta `vendor/`.

### Paso 3: Crear el archivo de configuración del entorno (`.env`)

Copie el archivo de plantilla `.env.example` y renómbrelo como `.env`:

**En PowerShell:**
```powershell
Copy-Item .env.example .env
```

**En CMD:**
```cmd
copy .env.example .env
```

### Paso 4: Iniciar el Servidor de Base de Datos (MySQL / MariaDB)

El proyecto utiliza **MySQL** como motor de base de datos local para mantener la máxima fidelidad y capacidades operativas.

1. **Si utiliza Laragon**: Simplemente abra Laragon y asegúrese de presionar **"Iniciar todo"** (Start All). Esto iniciará MySQL automáticamente.
2. **Si utiliza XAMPP**: Abra el Panel de Control de XAMPP e inicie el servicio **MySQL** (presionando el botón "Start" al lado de MySQL).

### Paso 5: Configurar el archivo de entorno (`.env`) y Crear la Base de Datos

**a)** Abra el archivo `.env` recién creado con un editor de texto (Bloc de Notas, VS Code, etc.) y verifique la sección de base de datos. Por defecto viene configurado para XAMPP/Laragon local:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=transparencia_santo_domingo
DB_USERNAME=root
DB_PASSWORD=
```
> **Nota**: Si su base de datos local tiene contraseña para el usuario `root`, especifíquela en `DB_PASSWORD=`.

**b)** Cree la base de datos e importar la estructura junto con los datos de prueba oficiales (recaudación, áreas, contribuyentes encriptados, etc.):

*   **Opción A (Desde phpMyAdmin):**
    1. Ingrese a **http://localhost/phpmyadmin** en su navegador.
    2. Cree una nueva base de datos llamada **`transparencia_santo_domingo`** con codificación `utf8mb4_unicode_ci`.
    3. Seleccione la base de datos recién creada, vaya a la pestaña **"Importar"**, seleccione el archivo `database/schema.sql` en la carpeta del proyecto y presione **"Importar" / "Continuar"**.

*   **Opción B (Desde Terminal / PowerShell):**
    Ejecute el siguiente comando para crear la base de datos e importar el script SQL consolidado:
    ```powershell
    # Crear la base de datos (con usuario root y sin contraseña por defecto)
    C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS transparencia_santo_domingo;"

    # Importar el esquema y las semillas de datos
    Get-Content database/schema.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root -D transparencia_santo_domingo
    ```

### Paso 6: Generar la llave de seguridad de la aplicación

Laravel requiere una clave criptográfica única para proteger las sesiones, cookies y los datos encriptados de los ciudadanos. Genérela con:

```powershell
php artisan key:generate
```

> Debe ver el mensaje: `Application key set successfully.`

---

## 3. Iniciar el Servidor Local

Una vez completados los 6 pasos anteriores, inicie el servidor de desarrollo de Laravel:

```powershell
php artisan serve
```

Verá un mensaje como:

```
INFO  Server running on [http://127.0.0.1:8000].
Press Ctrl+C to stop the server
```

### Abrir el portal en el navegador

Abra su navegador web preferido (Chrome, Firefox, Edge, etc.) e ingrese a:

### 👉 **http://127.0.0.1:8000**

¡El Portal de Transparencia Activa se cargará automáticamente y estará 100% funcional!

> **Para detener el servidor**: Regrese a la terminal y presione `Ctrl + C`.

---

## 4. Credenciales de Prueba

El sistema incluye 3 perfiles de usuario precargados en la base de datos para testear todas las funcionalidades del portal:

### Perfil A — Ciudadano (Aporte Bajo)
| Campo | Valor |
|-------|-------|
| **RUT** | `12.345.678-9` |
| **Contraseña** | `Pb_123@01` |
| **Rol** | Ciudadano |
| **Aporte Total Anual** | $728.000 |

### Perfil B — Ciudadano (Aporte Alto)
| Campo | Valor |
|-------|-------|
| **RUT** | `89.234.255-4` |
| **Contraseña** | `Pb_321@02` |
| **Rol** | Ciudadano |
| **Aporte Total Anual** | $5.000.000 |

### Perfil C — Administrador Municipal
| Campo | Valor |
|-------|-------|
| **RUT** | `8.765.432-1` |
| **Contraseña** | `Pb_123@03` |
| **Rol** | Administrador |
| **Acceso** | Panel de Administración y Carga de Datos |

---

## 5. Funcionalidades Disponibles para Testeo

### Páginas del Portal

| Página | URL | Descripción |
|--------|-----|-------------|
| **Inicio** | `http://127.0.0.1:8000` | Dashboard principal de Transparencia Activa con resumen general |
| **Destino de Impuestos** | `http://127.0.0.1:8000/pages/destino-impuestos.html` | Desglose interactivo de recaudación, áreas de inversión, proyectos y servicios |
| **Presupuesto Municipal** | `http://127.0.0.1:8000/pages/presupuesto.html` | Ejecución presupuestaria por área con barras de progreso dinámicas |
| **Inicio de Sesión** | `http://127.0.0.1:8000/pages/login.html` | Simulador de ClaveÚnica para ciudadanos y administrador |
| **Panel de Administración** | `http://127.0.0.1:8000/pages/admin.html` | Carga masiva de archivos CSV, gestión de períodos de consulta e historial de cargas (solo accesible con perfil Administrador) |

### Flujos de Testeo Sugeridos

**1. Flujo Ciudadano:**
   - Ingrese a la página de **Inicio** y explore el dashboard general.
   - Navegue a **Destino de Impuestos** para ver los gráficos interactivos de recaudación y distribución del gasto.
   - Vaya a **Inicio de Sesión** e ingrese con el **Perfil A** (`12.345.678-9` / `Pb_123@01`).
   - Observe cómo el sistema muestra el desglose personalizado del aporte del ciudadano.
   - Cierre sesión e ingrese con el **Perfil B** para comparar los montos de un contribuyente de aporte alto.

**2. Flujo Administrador:**
   - Ingrese a **Inicio de Sesión** con el **Perfil C** (`8.765.432-1` / `Pb_123@03`).
   - Note cómo aparece automáticamente el enlace **"Administración"** en la barra de navegación.
   - Acceda al **Panel de Administración**.
   - Descargue una plantilla CSV de ejemplo (ej. "Recaudación").
   - Suba el archivo CSV descargado para probar la carga masiva de datos.
   - Verifique que el historial de cargas registra la operación exitosa.
   - Gestione los **Períodos de Consulta** habilitando o deshabilitando años y meses específicos.

---

## 6. Estructura del Proyecto

```
municipalidad/
│
├── app/                          # Backend Laravel
│   ├── Http/Controllers/
│   │   └── ApiController.php     # Controlador principal de la API REST
│   ├── Models/
│   │   └── Contribuyente.php     # Modelo de datos de contribuyentes (encriptación)
│   └── Providers/                # Proveedores de servicios de Laravel
│
├── bootstrap/cache/              # Caché de arranque del framework
│
├── config/                       # Configuración del sistema
│   ├── app.php                   # Configuración general de la aplicación
│   ├── database.php              # Configuración de conexiones de base de datos
│   ├── session.php               # Configuración de sesiones
│   └── view.php                  # Configuración de vistas
│
├── database/
│   └── schema.sql                # Script SQL consolidado para MySQL/MariaDB (estructura y semillas)
│
├── public/                       # Directorio público (Frontend)
│   ├── index.html                # Página principal del portal
│   ├── index.php                 # Punto de entrada de Laravel
│   ├── css/
│   │   └── custom.css            # Estilos del portal
│   ├── js/
│   │   ├── app.js                # Lógica principal del frontend
│   │   ├── budget-engine.js      # Motor de cálculo presupuestario dinámico
│   │   └── tables.js             # Tablas interactivas con búsqueda y filtros
│   ├── pages/
│   │   ├── destino-impuestos.html  # Página de destino de impuestos
│   │   ├── presupuesto.html        # Página de ejecución presupuestaria
│   │   ├── login.html              # Inicio de sesión (ClaveÚnica simulada)
│   │   └── admin.html              # Panel de administración
│   ├── data/
│   │   └── destino-impuestos.json  # Datos JSON estáticos de respaldo
│   └── assets/                     # Imágenes y recursos visuales
│
├── routes/
│   ├── api.php                   # Definición de rutas de la API REST
│   └── web.php                   # Ruta raíz web (redirección a index.html)
│
├── storage/                      # Almacenamiento de logs, caché y sesiones
│
├── .env.example                  # Plantilla de configuración del entorno
├── .env                          # Configuración activa del entorno (se crea en el Paso 3)
├── composer.json                 # Definición de dependencias PHP
├── composer.lock                 # Versiones exactas de dependencias instaladas
├── artisan                       # CLI de Laravel
└── DESPLIEGUE_FINAL.md           # Este archivo
```

---

## 7. Notas Técnicas Importantes

### Base de Datos
- **Motor Utilizado**: Se utiliza **MySQL / MariaDB** como motor de base de datos oficial tanto para desarrollo, testeo local, como producción. Esto garantiza la total compatibilidad con phpMyAdmin y las políticas de la institución.
- **Conectores y Drivers**: La base de datos es administrada mediante el Driver nativo de MySQL integrado en Laravel, asegurando el rendimiento óptimo y el correcto funcionamiento de las transacciones bajo normas ISO.

### Seguridad y Privacidad (ISO 27001 / ISO 27701)
- Los **RUTs de los ciudadanos** se almacenan como hashes SHA-256 no reversibles en la columna `rut_hash`. El RUT original nunca se consulta en texto plano en la base de datos.
- Los **nombres y RUTs** para visualización se guardan cifrados con **AES-256-CBC** (cifrado de grado militar) usando la clave `APP_KEY` generada en el Paso 6.
- Las **contraseñas** se almacenan como hashes **Bcrypt** irreversibles.
- Las rutas de la API están protegidas con un **límite de 60 peticiones por minuto** para evitar la extracción masiva de datos.

### Compatibilidad
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 4, Material Icons
- **Navegadores compatibles**: Chrome, Firefox, Edge, Safari (versiones modernas)

---

## 8. Solución de Problemas Frecuentes

### ❌ `php : The term 'php' is not recognized...`
**Causa**: PHP no está instalado o no se encuentra en la variable de entorno PATH del sistema.
**Solución**: Instale PHP usando Laragon, XAMPP, o manualmente. Si lo instaló manualmente, asegúrese de agregar la carpeta de instalación de PHP a la variable `PATH` del sistema y reinicie la terminal.

### ❌ `composer : The term 'composer' is not recognized...`
**Causa**: Composer no está instalado.
**Solución**: Descargue e instale [Composer para Windows](https://getcomposer.org/Composer-Setup.exe). Reinicie la terminal después de la instalación.

### ❌ `The zip extension and unzip/7z commands are both missing`
**Causa**: La extensión `zip` de PHP no está habilitada.
**Solución**: Abra el archivo `php.ini` de su instalación de PHP y descomente la línea `extension=zip` (quite el `;` del inicio). Guarde y reinicie la terminal.

### ❌ `The openssl extension is missing`
**Causa**: La extensión `openssl` de PHP no está habilitada.
**Solución**: Abra el archivo `php.ini` y descomente la línea `extension=openssl`. Si usó Laragon o XAMPP, esta extensión ya viene habilitada por defecto.

### ❌ `The bootstrap/cache directory must be present and writable`
**Causa**: La carpeta `bootstrap/cache/` no existe.
**Solución**: Cree la carpeta manualmente:
```powershell
New-Item -Path bootstrap\cache -ItemType Directory -Force
```

### ❌ `SQLSTATE[HY000]: Connection refused...` o error al conectar a la base de datos
**Causa**: El servicio de MySQL no está iniciado o las credenciales en el archivo `.env` son incorrectas.
**Solución**: Asegúrese de que MySQL esté activo en el Panel de Control de Laragon/XAMPP. Verifique que `DB_USERNAME` y `DB_PASSWORD` en el archivo `.env` coincidan con sus credenciales de base de datos local.

### ❌ `SQLSTATE[42S02]: Base table or view not found...`
**Causa**: La base de datos está creada pero no se ha importado el archivo `schema.sql`.
**Solución**: Asegúrese de importar el archivo `database/schema.sql` en la base de datos `transparencia_santo_domingo` usando phpMyAdmin o mediante el comando de consola indicado en el Paso 5.

### ❌ `No application encryption key has been specified`
**Causa**: No se generó la clave de encriptación (Paso 6).
**Solución**: Ejecute `php artisan key:generate`.

### ❌ El navegador muestra una página en blanco o error 500
**Causa**: Faltan las carpetas de almacenamiento temporal de Laravel.
**Solución**: Cree las carpetas necesarias ejecutando:
```powershell
New-Item -Path storage\framework\sessions, storage\framework\views, storage\framework\cache\data, storage\logs -ItemType Directory -Force
```

---

## Resumen Rápido (Cheatsheet)

Si ya tiene PHP, Composer y MySQL (Laragon/XAMPP) activos, estos son los comandos que necesita ejecutar en orden para tener el proyecto funcionando:

```powershell
# 1. Instalar dependencias
composer install

# 2. Configurar entorno (.env)
Copy-Item .env.example .env

# 3. Crear base de datos e importar esquema en MySQL
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS transparencia_santo_domingo;"
Get-Content database/schema.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root -D transparencia_santo_domingo

# 4. Generar llave de seguridad
php artisan key:generate

# 5. Levantar el servidor
php artisan serve
```

Luego abra su navegador en **http://127.0.0.1:8000** y el portal estará operativo.

---

> **Documento generado para fines de evaluación y testeo del Portal de Transparencia Activa — I. Municipalidad de Santo Domingo.**
