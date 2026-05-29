# 🏛️ Portal de Transparencia Activa — Municipalidad de Santo Domingo

Portal web de **Transparencia Activa** de la I. Municipalidad de Santo Domingo, desarrollado conforme a la **Ley N° 20.285** sobre Acceso a la Información Pública, la **Norma Técnica del Kit Digital** del Gobierno de Chile y estándares de seguridad de datos personales (**ISO 27001** e **ISO 27701**).

Permite a los ciudadanos conocer en detalle cómo se recaudan y destinan sus impuestos municipales, el presupuesto comunal y la ejecución presupuestaria, resguardado tras un portal de acceso seguro y una administración dinámica de períodos de consulta.

---

## 📋 Características Clave

- **Inicio de Sesión Seguro (ClaveÚnica)**: Portal de login premium que emula la identidad digital del estado. Resguardado en el backend con algoritmos criptográficos robustos (**Bcrypt** y **SHA-256**).
- **Control de Privacidad (ISO 27701)**: Cifrado simétrico **AES-256-CBC** nativo en base de datos para proteger el nombre y RUT de los contribuyentes. Las sesiones se guardan temporalmente en `sessionStorage` para evitar fugas en terminales públicas.
- **Panel Administrativo Interno (`admin.html`)**: Permite al Administrador habilitar o deshabilitar períodos específicos de consulta (años completos o meses). Protegido mediante un script cortafuegos que bloquea y expulsa a usuarios sin privilegios.
- **Dashboards Dinámicos**: Los gráficos de dona SVG, las tablas interactivas con descargas CSV y las barras de aporte per cápita se recalculan y actualizan automáticamente según las contribuciones reales del usuario logueado en la base de datos (Ej: Alonso con $728.000 vs. Sofía con $5.000.000).
- **Arquitectura de Resiliencia Híbrida (Graceful Fallback)**:
  * **Con Backend (Producción/MySQL)**: Consume datos en tiempo real de la base de datos MySQL por medio de API endpoints de Laravel.
  * **Sin Backend (Desarrollo/Offline)**: Si el servidor PHP o la base de datos no están activos (ej. al correr sobre el puerto `5500` con Live Server), la aplicación lo autodetecta e inicia una simulación criptográfica local basada en `localStorage` y archivos JSON de respaldo. **¡El portal funciona al 100% en ambos modos sin romper la experiencia del evaluador!**

---

## 🛠️ Requisitos de Entorno

Para ejecutar la versión completa con conexión a base de datos y backend PHP/Laravel, necesitará:
- **PHP** 8.2 o superior (probado en PHP 8.3)
- **Composer** (gestor de dependencias PHP)
- **MySQL** / MariaDB (administrado vía phpMyAdmin, HeidiSQL, etc.)

---

## 🚀 Cómo Iniciar el Proyecto (Instalación y Uso)

Cuando una persona clona el repositorio desde GitHub, tiene **dos maneras** sumamente sencillas de abrir y verificar el proyecto:

---

### Opción A — Ejecución Completa (Backend Laravel + Base de Datos MySQL)

Siga estos pasos para montar el backend real y conectar la base de datos local:

1. **Clonar el Repositorio y Entrar a él**:
   ```bash
   git clone https://github.com/tu-usuario/Proyecto-Transparencia-Municipalidad-de-Santo-Domingo.git
   cd Proyecto-Transparencia-Municipalidad-de-Santo-Domingo
   ```

2. **Instalar dependencias de Composer**:
   ```bash
   composer install
   ```

3. **Configurar el archivo de Variables de Entorno (`.env`)**:
   * Duplica el archivo `.env.example` y llámalo `.env`:
     * En Windows: `copy .env.example .env`
     * En Linux/macOS: `cp .env.example .env`
   * *Nota: La llave criptográfica `APP_KEY` viene preconfigurada en el archivo para poder desencriptar correctamente las contribuciones y nombres de los perfiles semilla de prueba.*

4. **Crear e Importar la Base de Datos**:
   * Crea una base de datos MySQL llamada **`transparencia_santo_domingo`** en tu motor local (HeidiSQL, phpMyAdmin, DBeaver, etc.).
   * Importa el archivo consolidado **`database/schema.sql`** en ella. 
   * Si usas Laragon o la consola de comandos, puedes ejecutar la importación automática de tablas y semillas corriendo:
     ```bash
     php artisan tinker --execute="\Illuminate\Support\Facades\DB::unprepared(file_get_contents('database/schema.sql'));"
     ```

5. **Lanzar el Servidor de Laravel**:
   ```bash
   php artisan serve
   ```

6. **Abrir en tu Navegador**:
   Accede a la raíz de la carpeta pública del portal web en tu navegador:
   👉 **[http://127.0.0.1:8000/index.html](http://127.0.0.1:8000/index.html)**

---

### Opción B — Ejecución Rápida de Evaluador (Zero-Configuration Offline)

Si la persona **no tiene instalado PHP o MySQL en su computador** y solo desea verificar el funcionamiento del frontend, el inicio de sesión y la interactividad del dashboard:

1. **Abrir el proyecto en VS Code**.
2. **Ejecutar un servidor estático local**:
   * Haz clic derecho sobre `public/index.html` → **"Open with Live Server"** (Extensión Live Server, puerto `:5500`).
   * O levanta un servidor estático rápido con Node.js desde la raíz del proyecto:
     ```bash
     npx http-server public/ -p 5500
     ```
3. **Probar el Portal**:
   * Navega a **`http://127.0.0.1:5500/index.html`** (o el puerto levantado).
   * **¡Todo funcionará de inmediato!** Al iniciar sesión o interactuar con el panel administrativo, el sistema detectará el entorno estático y activará la simulación interactiva local para permitir un recorrido fluido de todas las pantallas, contraseñas y bloqueos.

---

## 🔑 Credenciales de Prueba para Verificación

El script semilla de la base de datos y la simulación offline incluyen tres perfiles con comportamientos completamente diferenciados para realizar pruebas exhaustivas:

### 👤 1. Ciudadano Común Perfil A (Monto Bajo)
- **RUT**: `12.345.678-9`
- **Contraseña**: `Pb_123@01`
- **Nombre**: Alonso Alexander Maurel Murgas
- **Comportamiento**: Ingresa al portal, se inyecta su nombre y muestra un aporte municipal total de **$728.000**. Los gráficos per cápita se recalculan con sus datos exactos.

### 👤 2. Ciudadano Común Perfil B (Monto Alto)
- **RUT**: `89.234.255-4`
- **Contraseña**: `Pb_321@02`
- **Nombre**: Sofía Elizabeth Álvarez Pérez
- **Comportamiento**: Ingresa al portal y muestra un aporte de **$5.000.000** con desgloses de altos montos, recalculando todos los gráficos y barras de distribución.

### 👑 3. Administrador Municipal
- **RUT**: `8.765.432-1`
- **Contraseña**: `Pb_123@03`
- **Nombre**: Administrador Municipal
- **Comportamiento**: Inicia sesión con privilegios administrativos. Inyecta dinámicamente la opción de **"Administración"** en el navbar de todas las páginas y le da acceso a [admin.html](file:///c:/Users/Dell/Desktop/Antigravity/Proyecto-Transparencia-Municipalidad-de-Santo-Domingo/public/pages/admin.html) para activar o desactivar años y meses completos en la consulta ciudadana.

---

## ♿ Accesibilidad y Estándares

El portal implementa los controles obligatorios de la Norma Técnica de Gobierno Digital:
- **Contraste Dinámico**: Alterna entre tema institucional y tema de alto contraste (fondo oscuro y tipografías amarillas legibles).
- **Ajuste de Fuentes**: Incrementa o reduce la tipografía en tres niveles (16px → 20px → 24px) para personas con visión reducida.
- **HTML Semántico**: Estructurado usando `header`, `main`, `footer`, `nav` y `section`.

---

<p align="center">
  <strong>I. Municipalidad de Santo Domingo</strong><br>
  Av. Padre Hurtado 398, Santo Domingo, Región de Valparaíso, Chile.<br>
  transparencia@santodomingo.cl · <a href="https://www.santodomingo.cl">santodomingo.cl</a>
</p>
