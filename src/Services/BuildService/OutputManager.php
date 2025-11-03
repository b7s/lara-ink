<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\BuildService;

use B7s\LaraInk\Services\AssetManagerService;
use B7s\LaraInk\Services\CompilerService;
use B7s\LaraInk\Services\SpaGeneratorService;
use B7s\LaraInk\Services\TranslationService;
use Illuminate\Support\Facades\File;

final class OutputManager
{
    public function __construct(
        private readonly CompilerService $compilerService,
        private readonly SpaGeneratorService $spaGenerator,
        private readonly TranslationService $translationService,
        private readonly AssetManagerService $assetManager,
    ) {}

    public function saveCompiledPage(string $slug, string $html): void
    {
        $pagesDir = ink_project_path(ink_config('output.pages_dir', 'public/pages'));
        $pagesRoot = rtrim($pagesDir, '/');

        $relativePath = trim($slug, '/');
        if ($relativePath === '') {
            $relativePath = 'index';
        }

        $segments = explode('/', $relativePath);
        $fileName = array_pop($segments) . '.html';

        $directorySuffix = empty($segments) ? '' : DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $directory = rtrim($pagesRoot . $directorySuffix, DIRECTORY_SEPARATOR);
        $fullPath = $directory . DIRECTORY_SEPARATOR . $fileName;

        // Minify HTML before saving
        $minifiedHtml = $this->compilerService->minifyHtml($html);

        File::ensureDirectoryExists($directory);
        File::put($fullPath, $minifiedHtml);
    }

    /**
     * @param array<int, string> $scriptPaths
     * @param array<int, string> $stylePaths
     */
    public function generateSpaIndex(array $scriptPaths, array $stylePaths): void
    {
        $outputPath = ink_project_path(ink_config('output.dir', 'public') . '/index.html');
        $this->spaGenerator->generateIndexHtml($outputPath, $scriptPaths, $stylePaths);
    }

    public function generateTranslations(): void
    {
        $outputPath = ink_project_path(ink_config('output.build_dir', 'public/build') . '/lara-ink-lang.js');
        $this->translationService->generateJsFile($outputPath);
    }

    public function cleanOutputDirectories(): void
    {
        $pagesDir = ink_project_path(ink_config('output.pages_dir', 'public/pages'));
        $buildDir = ink_project_path(ink_config('output.build_dir', 'public/build'));

        if (File::exists($pagesDir)) {
            File::cleanDirectory($pagesDir);
        }

        if (File::exists($buildDir)) {
            File::cleanDirectory($buildDir);
        }
    }

    /**
     * @return array{scripts: array<int, string>, styles: array<int, string>}
     */
    public function prepareAssets(): array
    {
        return [
            'scripts' => $this->assetManager->prepareScripts(),
            'styles' => $this->assetManager->prepareStyles(),
        ];
    }
}
