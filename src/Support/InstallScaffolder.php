<?php

declare(strict_types=1);

namespace B7s\LaraInk\Support;

use Illuminate\Filesystem\Filesystem;

final class InstallScaffolder
{
    private const POST_UPDATE_COMMAND = '@php artisan lara-ink:install';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * Scaffold LaraInk assets into the consuming application.
     *
     * @param callable(string):void|null $infoCallback Optional callback for progress messages.
     */
    public function scaffold(string $basePath, ?callable $infoCallback = null): void
    {
        $composerPath = $basePath . DIRECTORY_SEPARATOR . 'composer.json';

        $this->ensureDirectories($basePath);
        $this->publishConfig($basePath);
        $this->ensureDefaultLayout($basePath);
        $this->ensureGitignore($basePath);
        $this->publishStubPages($basePath, $infoCallback);
        $this->publishVitePlugin($basePath, $infoCallback);
        $this->addPostUpdateCmd($composerPath);
    }

    private function addPostUpdateCmd(string $composerJsonPath): void
    {
        if (!$this->filesystem->exists($composerJsonPath)) {
            return;
        }

        $composerJson = json_decode(
            $this->filesystem->get($composerJsonPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $composerJson['scripts'] ??= [];
        $composerJson['scripts']['post-update-cmd'] ??= [];

        if (!in_array(self::POST_UPDATE_COMMAND, $composerJson['scripts']['post-update-cmd'], true)) {
            $composerJson['scripts']['post-update-cmd'][] = self::POST_UPDATE_COMMAND;
        }

        $this->filesystem->put(
            $composerJsonPath,
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    private function ensureDirectories(string $basePath): void
    {
        $directories = [
            'resources/lara-ink/pages',
            'resources/lara-ink/layouts',
            'resources/lara-ink/components',
            'resources/lara-ink/assets',
            'lang',
            'public/build',
            'public/pages',
        ];

        foreach ($directories as $directory) {
            $path = $basePath . DIRECTORY_SEPARATOR . $directory;

            if ($this->filesystem->isDirectory($path)) {
                continue;
            }

            if (!$this->filesystem->makeDirectory($path, 0o755, true)) {
                throw new \RuntimeException(sprintf('Unable to create directory: %s', $path));
            }
        }
    }

    private function publishConfig(string $basePath): void
    {
        $target = $basePath . DIRECTORY_SEPARATOR . 'config/lara-ink.php';

        if ($this->filesystem->exists($target)) {
            return;
        }

        $source = __DIR__ . '/../../config/lara-ink.php';

        if (!$this->filesystem->exists($source)) {
            throw new \RuntimeException('LaraInk configuration file not found in package.');
        }

        $configDirectory = dirname($target);

        if (!$this->filesystem->isDirectory($configDirectory) &&
            !$this->filesystem->makeDirectory($configDirectory, 0o755, true)
        ) {
            throw new \RuntimeException(sprintf('Unable to create config directory: %s', $configDirectory));
        }

        if (!$this->filesystem->copy($source, $target)) {
            throw new \RuntimeException('Unable to publish LaraInk configuration file.');
        }
    }

    private function ensureDefaultLayout(string $basePath): void
    {
        $layoutPath = $basePath . DIRECTORY_SEPARATOR . 'resources/lara-ink/layouts/app.php';

        if ($this->filesystem->exists($layoutPath)) {
            return;
        }

        $layout = <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'LaraInk') }}</title>
    {!! $head ?? '' !!}
</head>
<body>
    {{ $slot ?? '' }}
</body>
</html>
BLADE;

        if ($this->filesystem->put($layoutPath, $layout) === false) {
            throw new \RuntimeException('Unable to create default LaraInk layout.');
        }
    }

    private function ensureGitignore(string $basePath): void
    {
        $gitignorePath = $basePath . DIRECTORY_SEPARATOR . 'resources/lara-ink/.gitignore';

        if ($this->filesystem->exists($gitignorePath)) {
            return;
        }

        if ($this->filesystem->put($gitignorePath, "*\n!.gitignore\n") === false) {
            throw new \RuntimeException('Unable to write LaraInk .gitignore.');
        }
    }

    private function publishStubPages(string $basePath, ?callable $infoCallback = null): void
    {
        $pagesDir = $basePath . DIRECTORY_SEPARATOR . 'resources/lara-ink/pages';
        
        $stubs = [
            'login-page.php' => 'login.php',
            'error-page.php' => 'error.php',
        ];

        foreach ($stubs as $stubFile => $targetFile) {
            $source = __DIR__ . '/../../stubs/' . $stubFile;
            $target = $pagesDir . DIRECTORY_SEPARATOR . $targetFile;

            if (!$this->filesystem->exists($source)) {
                continue;
            }

            // Only copy if target doesn't exist (don't overwrite user customizations)
            if ($this->filesystem->exists($target)) {
                continue;
            }

            if (!$this->filesystem->copy($source, $target)) {
                throw new \RuntimeException(sprintf('Unable to publish stub page: %s', $targetFile));
            }

            if ($infoCallback !== null) {
                $infoCallback(sprintf('Published page stub: %s', $targetFile));
            }
        }
    }

    private function publishVitePlugin(string $basePath, ?callable $infoCallback = null): void
    {
        $target = $basePath . DIRECTORY_SEPARATOR . 'vite-plugin-lara-ink.js';
        $source = __DIR__ . '/../../stubs/vite-plugin-lara-ink.js';

        if (!$this->filesystem->exists($source)) {
            return;
        }

        if (!$this->filesystem->copy($source, $target)) {
            throw new \RuntimeException('Unable to publish Vite plugin.');
        }

        if ($infoCallback !== null) {
            $infoCallback('Vite plugin published to project root.');
        }
    }
}
