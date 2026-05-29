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
