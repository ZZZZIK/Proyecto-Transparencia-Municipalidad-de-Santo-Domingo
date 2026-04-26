# 🏛️ Portal de Transparencia Activa — Municipalidad de Santo Domingo

Portal web de **Transparencia Activa** de la I. Municipalidad de Santo Domingo, desarrollado conforme a la **Ley N° 20.285** sobre Acceso a la Información Pública y la **Norma Técnica del Kit Digital** del Gobierno de Chile.

> Permite a los ciudadanos conocer en detalle cómo se recaudan y destinan los impuestos municipales, el estado del presupuesto comunal y la ejecución presupuestaria por área.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Instalación y Uso](#-instalación-y-uso)
- [Páginas del Portal](#-páginas-del-portal)
- [Arquitectura de Componentes](#-arquitectura-de-componentes)
- [Modelo de Datos](#-modelo-de-datos)
- [Accesibilidad](#-accesibilidad)
- [Cumplimiento Kit Digital](#-cumplimiento-kit-digital)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

---

## ✨ Características

- **Dashboard de Transparencia:** Visualización de recaudación, gasto ejecutado, ejecución presupuestaria y aporte per cápita.
- **Destino de Impuestos:** Desglose detallado por área (Educación, Salud, Seguridad, etc.), proyecto y servicio contratado.
- **Presupuesto Municipal:** Comparativa de montos asignados vs. ejecutados con filtros por año y mes.
- **Tablas Dinámicas:** Motor propio de tablas con búsqueda, ordenamiento, paginación y exportación CSV.
- **Gráficos CSS Puro:** Barras horizontales y donut SVG sin dependencias externas.
- **Accesibilidad:** Control de tamaño de fuente (3 niveles) y modo alto contraste.
- **Responsive:** Diseño adaptativo para desktop, tablet y móvil.

---

## 🛠️ Tecnologías

| Tecnología | Versión | Uso |
|---|---|---|
| **Framework Kit Digital** | CDN oficial | CSS + JS base del gobierno (`gob.cl.css`, `gob.cl.js`) |
| **Bootstrap** | 4.5 (integrado en gob.cl) | Sistema de grillas y componentes UI |
| **jQuery** | 3.5.1 (slim) | Requerido por Bootstrap para componentes interactivos |
| **Google Fonts** | — | Roboto, Roboto Slab, Roboto Mono |
| **Material Icons** | — | Iconografía |
| **JavaScript Vanilla** | ES6+ | Lógica de aplicación, gráficos y tablas |

### CDN utilizados

```html
<!-- Framework Kit Digital (incluye Bootstrap 4.5) -->
<link rel="stylesheet" href="https://cdn.digital.gob.cl/framework/css/gob.cl.css">
<script src="https://cdn.digital.gob.cl/framework/js/gob.cl.js"></script>

<!-- Tipografía e Iconos -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Slab:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- jQuery + Bootstrap Bundle -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 📁 Estructura del Proyecto

```
Proyecto-Transparencia-Municipalidad-de-Santo-Domingo/
├── index.html                   # Página principal (Dashboard)
├── README.md                    # Este archivo
│
├── pages/
│   ├── destino-impuestos.html   # Aporte ciudadano y destino de impuestos
│   └── presupuesto.html         # Presupuesto municipal y ejecución
│
├── css/
│   └── custom.css               # Estilos custom (extensión del Kit Digital)
│
├── js/
│   ├── app.js                   # Lógica principal (utilidades, gráficos, accesibilidad)
│   └── tables.js                # Motor de tablas dinámicas (DynamicTable)
│
├── data/
│   └── destino-impuestos.json   # Datos de recaudación y destino por área
│
└── assets/
    └── img/
        └── logo-municipalidad.png  # Escudo/logo institucional
```

---

## 🚀 Instalación y Uso

### Requisitos previos

- Un navegador web moderno (Chrome, Firefox, Edge, Safari)
- Conexión a internet (para cargar CDN del framework, fuentes e iconos)

### Ejecución local

**Opción 1 — Servidor estático con Node.js:**

```bash
# Instalar y ejecutar servidor HTTP
npx http-server . -p 8080 --cors

# Abrir en navegador
# http://localhost:8080
```

**Opción 2 — Extensión Live Server (VS Code):**

1. Instalar la extensión "Live Server" en VS Code.
2. Click derecho sobre `index.html` → "Open with Live Server".

**Opción 3 — Apertura directa:**

Abrir `index.html` directamente en el navegador. 

> ⚠️ **Nota:** La carga de datos JSON (`data/destino-impuestos.json`) requiere un servidor HTTP por restricciones CORS de los navegadores. Las páginas de destino-impuestos pueden no funcionar completamente al abrir como `file://`.

---

## 📄 Páginas del Portal

### 1. Inicio (`index.html`)

Página principal con dashboard de indicadores clave:

| Indicador | Descripción |
|---|---|
| Recaudación 2025 | Total recaudado en el año fiscal |
| Gasto Ejecutado | Total de gastos realizados |
| Ejecución Presupuestaria | Porcentaje del presupuesto ejecutado |
| Aporte por Habitante | Contribución promedio per cápita |

Incluye tarjetas de navegación a las secciones de **Destino de Impuestos** y **Presupuesto Municipal**.

### 2. Destino de Impuestos (`pages/destino-impuestos.html`)

Página más completa del portal con **5 pestañas (tabs)**:

| Pestaña | Contenido |
|---|---|
| **Destino de tu Aporte** | Distribución personalizada del aporte del contribuyente por área |
| **Recaudación por Ítem** | Desglose por fuente: Impuesto Territorial, Permisos, Patentes, etc. |
| **Destino por Área** | Distribución del gasto con gráfico donut + barras + tarjetas detalle |
| **% por Proyecto** | Tabla de proyectos con ID, monto, porcentaje y estado |
| **% por Servicio** | Tabla de servicios contratados externamente |

**Funcionalidades:**
- Filtros por **Año** (2023–2025) y **Mes** (anual o mensual)
- Tarjeta de usuario con simulación de Clave Única
- Cálculo dinámico del aporte per cápita

### 3. Presupuesto Municipal (`pages/presupuesto.html`)

Dashboard de ejecución presupuestaria con:
- 4 stat cards: Ingresos, Gastos, Superávit, % Ejecución
- Gráfico de barras por área
- Tabla dinámica con datos de ejecución

---

## 🏗️ Arquitectura de Componentes

### `app.js` — Módulo Principal

```javascript
const App = {
  formatCLP(n)           // Formatea números a pesos chilenos: $1.234.567
  formatDate(s)          // Formatea fechas ISO a formato chileno: dd/mm/yyyy
  initA11y()             // Inicializa controles de accesibilidad (fuente + contraste)
  initScrollAnimations() // Observador de intersección para animaciones al scroll
  renderBarChart(id, data, options)  // Renderiza gráfico de barras CSS
  renderDonut(id, data)  // Renderiza gráfico donut SVG
  loadJSON(path)         // Carga asíncrona de archivos JSON
  init()                 // Inicialización general al DOMContentLoaded
}
```

### `tables.js` — Motor de Tablas Dinámicas

Clase `DynamicTable` con las siguientes capacidades:

| Función | Descripción |
|---|---|
| **Búsqueda** | Filtrado en tiempo real con debounce de 300ms |
| **Ordenamiento** | Click en cabeceras para ordenar ASC/DESC |
| **Paginación** | Navegación por páginas con selector de tamaño (10/25/50) |
| **Filtros** | Filtrado programático por columna |
| **Exportar CSV** | Descarga de datos filtrados en formato CSV (UTF-8 con BOM) |
| **Tipos de celda** | `text`, `currency`, `date`, `percent`, `status`, `progress` |

**Uso:**

```javascript
// Registro global de tablas
window._tables['miTabla'] = new DynamicTable('miTabla', {
  data: [...],
  columns: [
    { key: 'nombre', label: 'Nombre', type: 'text' },
    { key: 'monto', label: 'Monto', type: 'currency' },
    { key: 'estado', label: 'Estado', type: 'status' }
  ],
  pageSize: 10
});
```

### `custom.css` — Estilos del Portal

Extensión del Framework Kit Digital con los siguientes componentes custom:

| Componente | Clase CSS | Uso |
|---|---|---|
| Barra Gobierno | `.gob-top-bar` | Barra oscura superior con logo y accesibilidad |
| Header | `.header-institucional` | Cabecera con logo y nombre institucional |
| Navegación | `.nav-main` | Barra de navegación azul primaria |
| Breadcrumb | `.breadcrumb-gov` | Migas de pan |
| Hero | `.page-hero` | Banner con gradiente para título de página |
| Stat Cards | `.stat-card` | Tarjetas de indicadores numéricos |
| Mosaic Cards | `.mosaic-card` | Tarjetas de navegación tipo dashboard |
| Info Cards | `.info-card` | Tarjetas de información detallada |
| Bar Chart | `.bar-chart`, `.bar-row` | Gráfico de barras horizontales CSS |
| Tablas | `.dynamic-table` | Tablas con estilos institucionales |
| Tags | `.tag`, `.tag-vigente`, etc. | Etiquetas de estado |
| Filtros | `.filter-panel` | Panel de filtros |
| Tabs | `.tabs-gov` | Pestañas estilo Kit Digital |
| Footer | `.footer-gov` | Pie de página institucional |

---

## 📊 Modelo de Datos

### `data/destino-impuestos.json`

```
{
  metadata: {
    ultimaActualizacion   // Fecha ISO (YYYY-MM-DD)
    fuente                // Dirección responsable
    periodoInformado      // Período fiscal
    recaudacionTotal      // Total recaudado (CLP)
    gastoTotal            // Total gastado (CLP)
    poblacionComuna       // Habitantes
  },
  resumenRecaudacion: {
    total                 // Total recaudación
    items: [{
      nombre              // Nombre del ítem (ej: "Impuesto Territorial")
      monto               // Monto en CLP
      porcentaje           // % del total
    }]
  },
  destinoPorArea: [{
    area                  // Nombre del área
    icono                 // Material Icon name
    color                 // Color hex
    montoAsignado         // Presupuesto asignado (CLP)
    porcentaje            // % del presupuesto total
    descripcion           // Descripción breve
    subItems: [{
      nombre              // Sub-ítem
      monto               // Monto asignado
    }]
  }],
  proyeccionesFinancieras: {
    anioProyectado        // Año siguiente
    ingresoProyectado     // Ingreso estimado
    gastoProyectado       // Gasto estimado
    proyeccionPorArea: [{ area, montoProyectado, variacion }]
  }
}
```

### Datos simulados (en script inline)

Las páginas `destino-impuestos.html` y `presupuesto.html` incluyen datos simulados directamente en el script para:

- **Datos de usuario:** Simulación de Clave Única con contribuciones por año
- **Proyectos:** Lista de 10 proyectos con ID, nombre, área, monto, estado
- **Servicios:** Lista de 7 servicios contratados externamente
- **Presupuesto por área:** 8 áreas con montos asignados y ejecutados

---

## ♿ Accesibilidad

El portal implementa las siguientes funcionalidades de accesibilidad conforme al Kit Digital:

| Función | Implementación | Clase Kit Digital |
|---|---|---|
| **Tamaño de fuente** | 3 niveles: 16px → 20px → 24px | `a11y-font-0`, `a11y-font-1`, `a11y-font-2` |
| **Alto contraste** | Fondo negro, texto blanco, bordes claros | `html.a11y-contrast` |
| **Navegación por teclado** | Botones accesibles con `title` y semántica HTML | — |
| **HTML semántico** | `<header>`, `<main>`, `<footer>`, `<nav>`, `<section>` | — |
| **ARIA** | `aria-label`, `aria-current`, `role="tablist/tab/tabpanel"` | — |

Los controles se ubican en la **barra superior de gobierno** (esquina derecha):
- 🔲 Botón de contraste
- **A-** Reducir texto
- **A+** Aumentar texto

---

## 🇨🇱 Cumplimiento Kit Digital

Este proyecto cumple con la [Norma Técnica del Kit Digital](https://kitdigital.gob.cl/) del Gobierno de Chile:

| Requisito | Estado | Detalle |
|---|---|---|
| Framework CSS oficial (`gob.cl.css`) | ✅ | CDN: `cdn.digital.gob.cl` |
| Framework JS oficial (`gob.cl.js`) | ✅ | CDN: `cdn.digital.gob.cl` |
| Barra superior de gobierno | ✅ | Logo SVG + link a gob.cl |
| Estructura HTML5 semántica | ✅ | `header > main > footer` |
| Tipografía Roboto / Roboto Slab | ✅ | Google Fonts |
| Paleta de colores institucional | ✅ | Primary `#006FB3`, Tertiary `#0A132D` |
| Bootstrap 4 | ✅ | Integrado en `gob.cl.css` |
| Accesibilidad (contraste + fuente) | ✅ | Clases `a11y-font-*` y `a11y-contrast` |
| Footer con links obligatorios | ✅ | División de Gobierno Digital + Gob.cl |
| Diseño responsive | ✅ | Breakpoints: 576/768/992/1200px |

---

## 🤝 Contribución

### Requisitos para contribuir

1. Respetar el estándar del Kit Digital del Gobierno de Chile
2. Mantener la estructura de archivos existente
3. Usar las variables CSS definidas en `custom.css` (`:root { --gob-* }`)
4. No agregar dependencias JS adicionales sin justificación
5. Seguir las convenciones de nombres existentes

### Agregar una nueva sección

1. Crear el archivo HTML en `pages/`
2. Copiar la estructura base (barra gobierno + header + nav + breadcrumb + main + footer)
3. Agregar el link en la navegación de **todos** los archivos HTML
4. Si requiere datos, crear un JSON en `data/`

### Variables CSS disponibles

```css
--gob-primary: #006FB3;     /* Azul GOB principal */
--gob-secondary: #FE6565;   /* Rojo/coral */
--gob-tertiary: #0A132D;    /* Azul oscuro */
--gob-neutral: #EEEEEE;     /* Gris claro */
--gob-white: #FFFFFF;
--gob-black: #212529;
--gob-gray-text: #4A4A4A;
--gob-gray-light: #F5F5F5;
--gob-gray-border: #DEE2E6;
--gob-green: #52B788;
--gob-orange: #F77F00;
--gob-purple: #7209B7;
--shadow-sm / --shadow-md / --shadow-lg
--radius-sm / --radius-md / --radius-lg
--transition: all 0.25s ease;
```

---

## 📝 Licencia

Proyecto desarrollado para la **I. Municipalidad de Santo Domingo**, Región de Valparaíso, Chile.

Desarrollado conforme a la Ley N° 20.285 de Acceso a la Información Pública y la Norma Técnica del Kit Digital de Gobierno de Chile.

---

<p align="center">
  <strong>I. Municipalidad de Santo Domingo</strong><br>
  Av. Padre Hurtado 398, Santo Domingo<br>
  (35) 2 283 6000 · transparencia@santodomingo.cl<br><br>
  <a href="https://www.santodomingo.cl">santodomingo.cl</a> · 
  <a href="https://kitdigital.gob.cl">Kit Digital</a> · 
  <a href="https://digital.gob.cl">División de Gobierno Digital</a>
</p>
