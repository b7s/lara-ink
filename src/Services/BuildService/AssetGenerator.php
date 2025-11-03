<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\BuildService;

use B7s\LaraInk\Services\ViteService;
use Illuminate\Support\Facades\File;

final class AssetGenerator
{
    public function __construct(
        private readonly ViteService $viteService,
    ) {}

    public function generateAssets(): void
    {
        $assetsPath = ink_resource_path('assets');

        if (!File::exists($assetsPath)) {
            File::makeDirectory($assetsPath, 0755, true);
        }

        $appCssPath = $assetsPath . DIRECTORY_SEPARATOR . 'app.css';
        if (!File::exists($appCssPath)) {
            $defaultCss = <<<'CSS'
/* LaraInk App CSS */

/* 
 * To use Tailwind CSS 4:
 * 
 * 1. Install dependencies:
 *    npm install -D tailwindcss @tailwindcss/vite
 * 
 * 2. Add to your vite.config.js:
 *    import tailwindcss from '@tailwindcss/vite';
 *    plugins: [tailwindcss()]
 * 
 * 3. Add at the top of this file:
 *    @import "tailwindcss";
 *    @source "../../lara-ink/pages/**/*.php";
 *    @source "../../lara-ink/layouts/**/*.php";
 */
CSS;
            File::put($appCssPath, $defaultCss);
        }

        $appJsPath = $assetsPath . DIRECTORY_SEPARATOR . 'app.js';
        if (!File::exists($appJsPath)) {
            File::put($appJsPath, "import './app.css';\n// LaraInk App JS");
        }

        $this->viteService->build();
        
        $buildDir = ink_project_path(ink_config('output.build_dir', 'public/build'));

        $this->viteService->generateManifest($buildDir);
        
        // Copy SPA router script to build directory
        $this->copySpaScript($buildDir);
    }
    
    private function copySpaScript(string $buildDir): void
    {
        $spaScriptSource = __DIR__ . '/../../../stubs/lara-ink-spa.js';
        $spaScriptDest = rtrim($buildDir, DIRECTORY_SEPARATOR) . '/lara-ink-spa.js';
        
        if (File::exists($spaScriptSource)) {
            File::ensureDirectoryExists($buildDir);
            File::copy($spaScriptSource, $spaScriptDest);
        }
    }
}
