<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Rutas de la API del Portal de Transparencia
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas de la API para el Portal de Transparencia Activa.
| Estas rutas son cargadas por el RouteServiceProvider dentro de un grupo que
| contiene el grupo de middleware "api".
|
| Cumpliendo con ISO 27001 e ISO/IEC 27701 (Protección de la Privacidad):
| Se aplica middleware de límite de peticiones (throttle:api) para mitigar
| ataques de denegación de servicio (DoS) y prevenir la descarga automatizada
| masiva de datos (Data Scraping) sobre las remuneraciones y aportes.
|
*/

Route::middleware('throttle:60,1')->group(function () {
    
    // Procesar inicio de sesión seguro (RUT + Contraseña)
    Route::post('/login', [ApiController::class, 'login']);
    
    // Obtener desglose de recaudación y destino de aportes (opcionalmente filtrado por RUT)
    Route::get('/destino-impuestos', [ApiController::class, 'getDestinoImpuestos']);
    
    // Obtener la ejecución presupuestaria por área
    Route::get('/presupuesto', [ApiController::class, 'getPresupuesto']);
    
    // Obtener proyectos municipales con filtros de búsqueda
    Route::get('/proyectos', [ApiController::class, 'getProyectos']);
    
    // Obtener servicios municipales contratados
    Route::get('/servicios', [ApiController::class, 'getServicios']);
    
    // Obtener períodos de consulta habilitados
    Route::get('/periodos', [ApiController::class, 'getPeriodos']);
    
    // Habilitar/Deshabilitar un período específico (requiere autenticación o simulación de rol admin)
    Route::post('/periodos/toggle', [ApiController::class, 'togglePeriodo']);
    
    // --- Rutas de Administración: Carga Masiva de Datos (RF11) ---
    
    // Cargar archivo CSV con datos de transparencia (solo Administrador)
    Route::post('/admin/upload', [ApiController::class, 'uploadTransparencia']);
    
    // Obtener historial de cargas realizadas por el Administrador
    Route::get('/admin/cargas', [ApiController::class, 'getHistorialCargas']);
    
    // Descargar plantilla CSV de ejemplo según el tipo de carga
    Route::get('/admin/plantilla/{tipo}', [ApiController::class, 'descargarPlantilla']);
    
    // Obtener resumen de contribuciones totales de todos los vecinos
    Route::get('/contribuyentes/resumen', [ApiController::class, 'getResumenContribuyentes']);
    Route::get('/contribuyentes_resumen', [ApiController::class, 'getResumenContribuyentes']);
});
