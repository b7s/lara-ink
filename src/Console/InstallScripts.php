<?php

declare(strict_types=1);

namespace B7s\LaraInk\Console;

use function Termwind\render;

final class InstallScripts
{
    public static function install(): void
    {
        $basePath = getcwd();

        if ($basePath === false) {
            return;
        }

        self::renderMessage('Preparing LaraInk scaffolding...', 'info');

        try {
            self::ensureDirectories($basePath);
            self::publishConfig($basePath);
            self::ensureDefaultLayout($basePath);
            self::ensureGitignore($basePath);

            self::renderMessage('LaraInk scaffolding finished successfully.', 'success');
        } catch (\Throwable $exception) {
            self::renderMessage('LaraInk scaffolding failed: ' . $exception->getMessage(), 'error');

            throw $exception;
        }
    }

    private static function ensureDirectories(string $basePath): void
    {
        $directories = [
            'resources/lara-ink/pages',
            'resources/lara-ink/layouts',
            'resources/lara-ink/assets',
            'lang',
            'public/build',
            'public/pages',
        ];

        foreach ($directories as $directory) {
            $path = $basePath . DIRECTORY_SEPARATOR . $directory;

            if (!is_dir($path) && !mkdir($path, 0o755, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Unable to create directory: %s', $path));
            }
        }
    }

    private static function publishConfig(string $basePath): void
    {
        $target = $basePath . DIRECTORY_SEPARATOR . 'config/lara-ink.php';

        if (file_exists($target)) {
            return;
        }

        $source = __DIR__ . '/../../config/lara-ink.php';

        if (!file_exists($source)) {
            throw new \RuntimeException('LaraInk configuration file not found in package.');
        }

        $configDirectory = dirname($target);

        if (!is_dir($configDirectory) && !mkdir($configDirectory, 0o755, true) && !is_dir($configDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create config directory: %s', $configDirectory));
        }

        if (!copy($source, $target)) {
            throw new \RuntimeException('Unable to publish LaraInk configuration file.');
        }

    }

    private static function ensureDefaultLayout(string $basePath): void
    {
        $layoutPath = $basePath . DIRECTORY_SEPARATOR . 'resources/lara-ink/layouts/app.php';

        if (file_exists($layoutPath)) {
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

        if (file_put_contents($layoutPath, $layout) === false) {
            throw new \RuntimeException('Unable to create default LaraInk layout.');
        }

    }

    private static function ensureGitignore(string $basePath): void
    {
        $gitignorePath = $basePath . DIRECTORY_SEPARATOR . 'resources/lara-ink/.gitignore';

        if (file_exists($gitignorePath)) {
            return;
        }

        $content = <<<'TXT'
*
!.gitignore
TXT;

        if (file_put_contents($gitignorePath, $content) === false) {
            throw new \RuntimeException('Unable to write LaraInk .gitignore.');
        }

    }

    private static function renderMessage(string $message, string $variant = 'info'): void
    {
        if (!self::shouldRender()) {
            return;
        }

        $message = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        [$bg, $text] = match ($variant) {
            'success' => ['bg-green-600', 'text-white'],
            'error' => ['bg-rose-600', 'text-white'],
            default => ['bg-cyan-600', 'text-white'],
        };

        render(<<<HTML
<div class="px-3 py-1 {$bg} {$text} mb-1">
    <span class="font-bold">LaraInk:</span>
    <span class="ml-1">{$message}</span>
</div>
HTML);
    }

    private static function shouldRender(): bool
    {
        return PHP_SAPI === 'cli' && function_exists('Termwind\\render');
    }
}
