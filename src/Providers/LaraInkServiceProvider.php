<?php

declare(strict_types=1);

namespace B7s\LaraInk\Providers;

use B7s\LaraInk\Console\Commands\BuildCommand;
use B7s\LaraInk\Services\AssetManagerService;
use B7s\LaraInk\Services\BladeCompilerService;
use B7s\LaraInk\Services\BuildService;
use B7s\LaraInk\Services\CacheService;
use B7s\LaraInk\Services\CompilerService;
use B7s\LaraInk\Services\DslParserService;
use B7s\LaraInk\Services\LayoutService;
use B7s\LaraInk\Services\RouteService;
use B7s\LaraInk\Services\SpaGeneratorService;
use B7s\LaraInk\Services\TranslationService;
use B7s\LaraInk\Services\ViteService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaraInkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/lara-ink.php',
            'lara-ink'
        );

        $this->registerServices();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/lara-ink.php' => config_path('lara-ink.php'),
        ], 'lara-ink-config');

        $this->registerCommands();
        $this->registerRoutes();
    }

    private function registerServices(): void
    {
        $this->app->singleton(RouteService::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(TranslationService::class);
        $this->app->singleton(DslParserService::class);
        $this->app->singleton(LayoutService::class);
        $this->app->singleton(ViteService::class);
        $this->app->singleton(BladeCompilerService::class);
        $this->app->singleton(AssetManagerService::class);

        $this->app->singleton(CompilerService::class, function ($app) {
            return new CompilerService(
                $app->make(TranslationService::class),
                $app->make(RouteService::class),
                $app->make(BladeCompilerService::class)
            );
        });

        $this->app->singleton(SpaGeneratorService::class, function ($app) {
            return new SpaGeneratorService(
                $app->make(RouteService::class),
                $app->make(CacheService::class)
            );
        });

        $this->app->singleton(BuildService::class, function ($app) {
            return new BuildService(
                $app->make(DslParserService::class),
                $app->make(CompilerService::class),
                $app->make(LayoutService::class),
                $app->make(RouteService::class),
                $app->make(TranslationService::class),
                $app->make(CacheService::class),
                $app->make(SpaGeneratorService::class),
                $app->make(ViteService::class),
                $app->make(AssetManagerService::class)
            );
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        $prefix = config('lara-ink.auth.route.prefix', '/api/ink');

        Route::prefix($prefix)
            ->middleware(['api'])
            ->group(function () {
                Route::post('/login', [\B7s\LaraInk\Http\Controllers\AuthController::class, 'login'])
                    ->name('lara-ink.login');

                Route::post('/logout', [\B7s\LaraInk\Http\Controllers\AuthController::class, 'logout'])
                    ->middleware('auth:sanctum')
                    ->name('lara-ink.logout');

                Route::get('/is-authenticated', [\B7s\LaraInk\Http\Controllers\AuthController::class, 'isAuthenticated'])
                    ->middleware('auth:sanctum')
                    ->name('lara-ink.is-authenticated');
            });
    }
}
