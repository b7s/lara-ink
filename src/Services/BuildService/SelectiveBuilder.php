<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\BuildService;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class SelectiveBuilder
{
    public function __construct(
        private readonly PageDiscovery $pageDiscovery,
        private readonly PageCompiler $pageCompiler,
        private readonly AssetGenerator $assetGenerator,
        private readonly OutputManager $outputManager,
    ) {}

    /**
     * Build only specific file(s) based on changed path
     * 
     * @return array{success: bool, message: string, pages: int, type: string}
     */
    public function buildSelective(string $changedPath): array
    {
        try {
            // Normalize path
            $changedPath = str_replace('\\', '/', $changedPath);
            $relativePath = str_replace(base_path() . '/', '', $changedPath);
            $type = $this->pageDiscovery->detectChangeType($relativePath);
            $pagesToBuild = [];

            match ($type) {
                'page' => $pagesToBuild = [$changedPath],
                'layout' => $pagesToBuild = $this->pageDiscovery->getPagesUsingLayout($changedPath),
                'component' => $pagesToBuild = $this->pageDiscovery->getPagesUsingComponent($changedPath),
                default => $pagesToBuild = $this->pageDiscovery->discoverPages(), // Fallback to full build
            };

            if (empty($pagesToBuild)) {
                return [
                    'success' => false,
                    'message' => "No pages to rebuild for {$type}: " . basename($changedPath),
                    'pages' => 0,
                    'type' => $type,
                ];
            }

            $compiledPages = [];
            foreach ($pagesToBuild as $pagePath) {
                if (!File::exists($pagePath)) {
                    continue;
                }
                
                try {
                    $compiledPages[] = $this->pageCompiler->compilePage($pagePath);
                } catch (\Exception $e) {
                    // Log error but continue with other pages
                    Log::error("Failed to compile page: {$pagePath}", ['error' => $e->getMessage()]);
                }
            }

            if (empty($compiledPages)) {
                return [
                    'success' => false,
                    'message' => "Failed to compile any pages. Check if file exists: {$changedPath}",
                    'pages' => 0,
                    'type' => $type,
                ];
            }

            // Only regenerate assets if needed
            if ($type !== 'page') {
                $this->assetGenerator->generateAssets();
                $assets = $this->outputManager->prepareAssets();
                $this->outputManager->generateSpaIndex($assets['scripts'], $assets['styles']);
                $this->outputManager->generateTranslations();
            }

            foreach ($compiledPages as $compiledPage) {
                $this->outputManager->saveCompiledPage($compiledPage['slug'], $compiledPage['html']);
            }

            return [
                'success' => true,
                'message' => "Rebuilt {$type}: " . basename($changedPath),
                'pages' => count($compiledPages),
                'type' => $type,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'pages' => 0,
                'type' => 'error',
            ];
        }
    }
}
