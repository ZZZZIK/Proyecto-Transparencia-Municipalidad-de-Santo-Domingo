<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Comprobar Modo de Mantenimiento
|--------------------------------------------------------------------------
|
| Si el portal está en mantenimiento programado, cargamos la vista estática
| para el usuario final sin levantar toda la estructura.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Cargar el Autocargador (Autoloader)
|--------------------------------------------------------------------------
|
| Requerimos las dependencias instaladas por Composer para poder operar.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Ejecutar la Aplicación
|--------------------------------------------------------------------------
|
| Instanciamos la aplicación, ejecutamos el kernel HTTP para procesar la
| petición web de transparencia y enviamos la respuesta al navegador.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
