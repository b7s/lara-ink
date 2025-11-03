<?php

declare(strict_types=1);

namespace B7s\LaraInk\Providers;

use B7s\LaraInk\Console\Commands\BuildCommand;
use B7s\LaraInk\Console\Commands\BuildSelectiveCommand;
use B7s\LaraInk\Console\Commands\DevCommand;
use B7s\LaraInk\Console\Commands\InstallCommand;
use B7s\LaraInk\Services\AssetManagerService;
use B7s\LaraInk\Services\BladeCompilerService;
use B7s\LaraInk\Services\BuildService;
use B7s\LaraInk\Services\BuildService\AssetGenerator;
use B7s\LaraInk\Services\BuildService\OutputManager;
use B7s\LaraInk\Services\BuildService\PageCompiler;
use B7s\LaraInk\Services\BuildService\PageDiscovery;
use B7s\LaraInk\Services\BuildService\SelectiveBuilder;
use B7s\LaraInk\Services\CacheService;
use B7s\LaraInk\Services\CompilerService;
use B7s\LaraInk\Services\CompilerService\ComponentProcessor;
use B7s\LaraInk\Services\CompilerService\CssCompiler;
use B7s\LaraInk\Services\CompilerService\HtmlCompiler;
use B7s\LaraInk\Services\CompilerService\JsCompiler;
use B7s\LaraInk\Services\CompilerService\Minifier;
use B7s\LaraInk\Services\CompilerService\TranslationTransformer;
use B7s\LaraInk\Services\CompilerService\VariableTransformer;
use B7s\LaraInk\Services\ComponentService;
use B7s\LaraInk\Services\ComponentService\AttributeParser;
use B7s\LaraInk\Services\ComponentService\ComponentCompiler;
use B7s\LaraInk\Services\ComponentService\ComponentDiscovery;
use B7s\LaraInk\Services\ComponentService\ComponentLoader;
use B7s\LaraInk\Services\ComponentService\ElementExtractor;
use B7s\LaraInk\Services\DslParserService;
use B7s\LaraInk\Services\DslParserService\ConfigExtractor;
use B7s\LaraInk\Services\DslParserService\RouteParamExtractor;
use B7s\LaraInk\Services\DslParserService\SlugGenerator;
use B7s\LaraInk\Services\DslParserService\TranslationExtractor;
use B7s\LaraInk\Services\DslParserService\TypeDetector;
use B7s\LaraInk\Services\DslParserService\VariableExtractor;
use B7s\LaraInk\Services\ExternalScriptCacheService;
use B7s\LaraInk\Services\LayoutService;
use B7s\LaraInk\Services\LayoutService\HeadElementsExtractor;
use B7s\LaraInk\Services\LayoutService\LayoutRenderer;
use B7s\LaraInk\Services\LayoutService\LayoutResolver;
use B7s\LaraInk\Services\LayoutService\TranslationPlaceholderHandler;
use B7s\LaraInk\Services\PageRouteRegistrationService;
use B7s\LaraInk\Services\RouteService;
use B7s\LaraInk\Services\SeoService;
use B7s\LaraInk\Services\SpaGeneratorService;
use B7s\LaraInk\Services\TranslationService;
use B7s\LaraInk\Services\ViteService;
use B7s\LaraInk\Support\InstallScaffolder;
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
        
        // DslParserService dependencies
        $this->app->singleton(ConfigExtractor::class);
        $this->app->singleton(TypeDetector::class);
        $this->app->singleton(VariableExtractor::class);
        $this->app->singleton(SlugGenerator::class);
        $this->app->singleton(RouteParamExtractor::class);
        $this->app->singleton(TranslationExtractor::class);
        
        $this->app->singleton(DslParserService::class, function ($app) {
            return new DslParserService(
                $app->make(ConfigExtractor::class),
                $app->make(VariableExtractor::class),
                $app->make(SlugGenerator::class),
                $app->make(RouteParamExtractor::class),
                $app->make(TranslationExtractor::class)
            );
        });
        $this->app->singleton(SeoService::class);
        $this->app->singleton(ViteService::class);
        $this->app->singleton(BladeCompilerService::class);
        $this->app->singleton(AssetManagerService::class);
        $this->app->singleton(ExternalScriptCacheService::class);
        $this->app->singleton(InstallScaffolder::class);
        $this->app->singleton(PageRouteRegistrationService::class);

        // LayoutService dependencies
        $this->app->singleton(LayoutResolver::class);
        $this->app->singleton(HeadElementsExtractor::class);
        $this->app->singleton(TranslationPlaceholderHandler::class);
        
        $this->app->singleton(LayoutRenderer::class, function ($app) {
            return new LayoutRenderer(
                $app->make(SeoService::class),
                $app->make(HeadElementsExtractor::class),
                $app->make(TranslationPlaceholderHandler::class)
            );
        });

        $this->app->singleton(LayoutService::class, function ($app) {
            return new LayoutService(
                $app->make(LayoutResolver::class),
                $app->make(LayoutRenderer::class)
            );
        });

        // ComponentService dependencies
        $this->app->singleton(ComponentDiscovery::class);
        $this->app->singleton(ElementExtractor::class);
        
        $this->app->singleton(ComponentLoader::class, function ($app) {
            return new ComponentLoader(
                $app->make(ComponentDiscovery::class)
            );
        });
        
        $this->app->singleton(AttributeParser::class);
        
        $this->app->singleton(ComponentCompiler::class, function ($app) {
            return new ComponentCompiler(
                $app->make(ElementExtractor::class)
            );
        });
        
        $this->app->singleton(ComponentService::class, function ($app) {
            return new ComponentService(
                $app->make(ComponentDiscovery::class),
                $app->make(ComponentLoader::class),
                $app->make(AttributeParser::class),
                $app->make(ComponentCompiler::class)
            );
        });

        // CompilerService dependencies
        $this->app->singleton(TranslationTransformer::class);
        $this->app->singleton(VariableTransformer::class);
        $this->app->singleton(Minifier::class);
        
        $this->app->singleton(ComponentProcessor::class, function ($app) {
            return new ComponentProcessor(
                $app->make(ComponentService::class),
                $app->make(BladeCompilerService::class),
                $app->make(TranslationTransformer::class)
            );
        });
        
        $this->app->singleton(HtmlCompiler::class, function ($app) {
            return new HtmlCompiler(
                $app->make(BladeCompilerService::class),
                $app->make(ComponentProcessor::class),
                $app->make(TranslationTransformer::class),
                $app->make(VariableTransformer::class)
            );
        });
        
        $this->app->singleton(JsCompiler::class, function ($app) {
            return new JsCompiler(
                $app->make(TranslationTransformer::class)
            );
        });
        
        $this->app->singleton(CssCompiler::class);

        $this->app->singleton(CompilerService::class, function ($app) {
            return new CompilerService(
                $app->make(HtmlCompiler::class),
                $app->make(JsCompiler::class),
                $app->make(CssCompiler::class),
                $app->make(VariableTransformer::class),
                $app->make(Minifier::class)
            );
        });

        $this->app->singleton(SpaGeneratorService::class, function ($app) {
            return new SpaGeneratorService(
                $app->make(RouteService::class),
                $app->make(CacheService::class),
                $app->make(ViteService::class)
            );
        });

        // BuildService dependencies
        $this->app->singleton(PageDiscovery::class);
        
        $this->app->singleton(PageCompiler::class, function ($app) {
            return new PageCompiler(
                $app->make(DslParserService::class),
                $app->make(CompilerService::class),
                $app->make(LayoutService::class),
                $app->make(RouteService::class),
                $app->make(TranslationService::class),
                $app->make(CacheService::class),
                $app->make(SeoService::class),
                $app->make(PageRouteRegistrationService::class)
            );
        });
        
        $this->app->singleton(AssetGenerator::class, function ($app) {
            return new AssetGenerator(
                $app->make(ViteService::class)
            );
        });
        
        $this->app->singleton(OutputManager::class, function ($app) {
            return new OutputManager(
                $app->make(CompilerService::class),
                $app->make(SpaGeneratorService::class),
                $app->make(TranslationService::class),
                $app->make(AssetManagerService::class)
            );
        });
        
        $this->app->singleton(SelectiveBuilder::class, function ($app) {
            return new SelectiveBuilder(
                $app->make(PageDiscovery::class),
                $app->make(PageCompiler::class),
                $app->make(AssetGenerator::class),
                $app->make(OutputManager::class)
            );
        });

        $this->app->singleton(BuildService::class, function ($app) {
            return new BuildService(
                $app->make(PageDiscovery::class),
                $app->make(PageCompiler::class),
                $app->make(AssetGenerator::class),
                $app->make(OutputManager::class),
                $app->make(SelectiveBuilder::class),
                $app->make(PageRouteRegistrationService::class)
            );
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
                BuildSelectiveCommand::class,
                DevCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        $prefix = config('lara-ink.auth.route.api_prefix', '/api/ink');

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
