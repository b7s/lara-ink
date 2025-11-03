<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\Services\BuildService\AssetGenerator;
use B7s\LaraInk\Services\BuildService\OutputManager;
use B7s\LaraInk\Services\BuildService\PageCompiler;
use B7s\LaraInk\Services\BuildService\PageDiscovery;
use B7s\LaraInk\Services\BuildService\SelectiveBuilder;

final class BuildService
{
    public function __construct(
        private readonly PageDiscovery $pageDiscovery,
        private readonly PageCompiler $pageCompiler,
        private readonly AssetGenerator $assetGenerator,
        private readonly OutputManager $outputManager,
        private readonly SelectiveBuilder $selectiveBuilder,
        private readonly PageRouteRegistrationService $pageRouteRegistration,
    ) {}

    /**
     * @return array{success: bool, message: string, pages: int}
     */
    public function build(): array
    {
        try {
            $pages = $this->pageDiscovery->discoverPages();
            
            if (empty($pages)) {
                return [
                    'success' => false,
                    'message' => 'No pages found in resources/lara-ink/pages/',
                    'pages' => 0,
                ];
            }

            $compiledPages = [];

            foreach ($pages as $pagePath) {
                $compiledPages[] = $this->pageCompiler->compilePage($pagePath);
            }

            $this->outputManager->cleanOutputDirectories();

            $this->assetGenerator->generateAssets();

            $assets = $this->outputManager->prepareAssets();

            $this->outputManager->generateSpaIndex($assets['scripts'], $assets['styles']);
            $this->outputManager->generateTranslations();

            foreach ($compiledPages as $compiledPage) {
                $this->outputManager->saveCompiledPage($compiledPage['slug'], $compiledPage['html']);
            }

            // Register all page routes with middleware in Laravel
            $this->pageRouteRegistration->registerRoutesInLaravel();

            return [
                'success' => true,
                'message' => 'Build completed successfully',
                'pages' => count($compiledPages),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'pages' => 0,
            ];
        }
    }

    /**
     * Build only specific file(s) based on changed path
     * 
     * @return array{success: bool, message: string, pages: int, type: string}
     */
    public function buildSelective(string $changedPath): array
    {
        return $this->selectiveBuilder->buildSelective($changedPath);
    }
}
