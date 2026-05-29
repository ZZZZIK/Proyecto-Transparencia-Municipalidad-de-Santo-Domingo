<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web de la Aplicación
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas web para la aplicación. Estas rutas son
| cargadas por el RouteServiceProvider.
|
*/

// Redirigir el índice raíz web al dashboard de Transparencia Activa (/index.html en public/)
Route::get('/', function () {
    return redirect('/index.html');
});
