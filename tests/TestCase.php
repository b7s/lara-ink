<?php

namespace Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapApplication();
        $this->registerConfig();
        $this->prepareFilesystem();
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    private function bootstrapApplication(): void
    {
        $basePath = dirname(__DIR__);

        $this->app = new Application($basePath);
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->filesystem = new Filesystem();

        $this->app->singleton('files', fn (): Filesystem => $this->filesystem);
    }

    private function registerConfig(): void
    {
        $laraInkConfig = require $this->app->basePath('config/lara-ink.php');

        $config = new Repository([
            'app' => [
                'name' => 'LaraInk Tests',
                'env' => 'testing',
                'debug' => true,
                'locale' => 'en',
                'fallback_locale' => 'en',
                'key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
                'timezone' => 'UTC',
            ],
            'lara-ink' => $laraInkConfig,
        ]);

        $this->app->instance('config', $config);
    }

    private function prepareFilesystem(): void
    {
        $this->filesystem->ensureDirectoryExists($this->app->basePath('lang'));
        $this->filesystem->ensureDirectoryExists($this->app->basePath('resources/lara-ink/pages'));
        $this->filesystem->ensureDirectoryExists($this->app->basePath('resources/lara-ink/layouts'));
        $this->filesystem->ensureDirectoryExists($this->app->basePath('resources/lara-ink/assets'));
        $this->filesystem->ensureDirectoryExists($this->app->basePath('public/build'));
        $this->filesystem->ensureDirectoryExists($this->app->basePath('public/pages'));

        $this->app->instance('path.lang', $this->app->basePath('lang'));
    }
}
