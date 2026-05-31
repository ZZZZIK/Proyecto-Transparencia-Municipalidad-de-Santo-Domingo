<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * El path de redirección por defecto para el login.
     *
     * @var string
     */
    public const HOME = '/index.html';

    /**
     * Define las rutas de tu aplicación.
     */
    public function boot(): void
    {
        // Configurar valores por defecto de autenticación para evitar "Auth guard [] is not defined"
        config([
            'auth.defaults.guard' => 'web',
            'auth.guards.web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'auth.providers.users' => [
                'driver' => 'eloquent',
                'model' => \App\Models\Contribuyente::class,
            ],
        ]);

        // Registrar middleware groups y aliases dinámicamente para compensar la falta de Kernel.php
        Route::aliasMiddleware('throttle', \Illuminate\Routing\Middleware\ThrottleRequests::class);
        Route::aliasMiddleware('bindings', \Illuminate\Routing\Middleware\SubstituteBindings::class);
        
        Route::middlewareGroup('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        Route::middlewareGroup('web', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Configurar límite de peticiones (Rate Limiters)
        $this->configureRateLimiting();

        $this->routes(function () {
            // Rutas de API con Rate Limiting y prefijo /api
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Rutas Web básicas
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configurar los limitadores de tráfico para la aplicación.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
