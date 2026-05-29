<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nombre de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Define el nombre de la institución y aplicación.
    |
    */

    'name' => env('APP_NAME', 'Transparencia Santo Domingo'),

    /*
    |--------------------------------------------------------------------------
    | Entorno de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Indica si el sistema está en desarrollo (local) o producción.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Modo de Depuración (Debug)
    |--------------------------------------------------------------------------
    |
    | En producción debe ser FALSE para evitar fugas de información sensible.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL de la Aplicación
    |--------------------------------------------------------------------------
    |
    | La dirección URL base utilizada para generar enlaces absolutos.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Zona Horaria
    |--------------------------------------------------------------------------
    |
    | Configurada para Chile (Santo Domingo, Región de Valparaíso).
    |
    */

    'timezone' => 'America/Santiago',

    /*
    |--------------------------------------------------------------------------
    | Configuración de Idioma
    |--------------------------------------------------------------------------
    |
    | Define el español como el idioma predeterminado de respuesta del sistema.
    |
    */

    'locale' => 'es',

    'fallback_locale' => 'en',

    'faker_locale' => 'es_ES',

    /*
    |--------------------------------------------------------------------------
    | Llave de Encriptación
    |--------------------------------------------------------------------------
    |
    | Utilizada para encriptar de forma robusta las cookies y los atributos
    | cifrados de la base de datos (como el RUT e identidad ciudadana).
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Proveedores de Servicios Autocargados
    |--------------------------------------------------------------------------
    |
    | Proveedores esenciales para arrancar la arquitectura del framework.
    |
    */

    'providers' => [

        /*
         * Proveedores de Servicios del Framework Laravel...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Proveedores de Servicios de la Aplicación...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],

];
