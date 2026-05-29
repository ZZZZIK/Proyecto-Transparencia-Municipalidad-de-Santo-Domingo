<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conexión por Defecto a la Base de Datos
    |--------------------------------------------------------------------------
    |
    | Define la conexión de base de datos activa por defecto.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Conexiones de Base de Datos
    |--------------------------------------------------------------------------
    |
    | Registro de configuraciones para cada driver de base de datos.
    |
    */

    'connections' => [

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'transparencia_santo_domingo'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Repositorio de Migraciones
    |--------------------------------------------------------------------------
    |
    | Nombre de la tabla de control de migraciones en MySQL.
    |
    */

    'migrations' => 'migrations',

];
