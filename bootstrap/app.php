<?php

/*
|--------------------------------------------------------------------------
| Crear la Aplicación
|--------------------------------------------------------------------------
|
| La primera acción es instanciar la aplicación de Laravel, la cual actúa
| como el contenedor de inversión de control (IoC) uniendo todos los
| componentes esenciales del framework.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Enlazar Interfaces Importantes
|--------------------------------------------------------------------------
|
| Enlazamos los nucleos (Kernels) de peticiones HTTP (Web) y de Consola (CLI)
| para que Laravel pueda interpretar tanto las peticiones de los navegadores
| como los comandos Artisan de Laragon.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    Illuminate\Foundation\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Illuminate\Foundation\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Illuminate\Foundation\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Retornar la Aplicación
|--------------------------------------------------------------------------
|
| Este script devuelve la instancia de la aplicación configurada para que
| sea ejecutada por el archivo index.php o el comando Artisan.
|
*/

return $app;
