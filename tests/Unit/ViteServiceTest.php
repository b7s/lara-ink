<?php

declare(strict_types=1);

use B7s\LaraInk\Services\ViteService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->service = new ViteService();
    // Use the actual configured build directory so getAssetUrl() can find the manifest
    $this->testBuildDir = ink_project_path(ink_config('output.build_dir', 'public/lara-ink/build'));
});

afterEach(function () {
    if (File::exists($this->testBuildDir)) {
        File::deleteDirectory($this->testBuildDir);
    }
});

describe('ViteService', function () {
    it('generates manifest with hashed files', function () {
        File::ensureDirectoryExists($this->testBuildDir);
        File::ensureDirectoryExists($this->testBuildDir . '/assets');
        
        // Create fake hashed files
        File::put($this->testBuildDir . '/app-abc123.js', '// JS content');
        File::put($this->testBuildDir . '/assets/app-def456.css', '/* CSS content */');
        
        $this->service->generateManifest($this->testBuildDir);
        
        $manifestPath = $this->testBuildDir . '/manifest.json';
        expect(File::exists($manifestPath))->toBeTrue();
        
        $manifest = json_decode(File::get($manifestPath), true);
        
        expect($manifest)
            ->toHaveKey('app.js')
            ->toHaveKey('app.css');
        
        expect($manifest['app.js'])
            ->toHaveKey('file', 'app-abc123.js')
            ->toHaveKey('hash', 'abc123');
        
        expect($manifest['app.css'])
            ->toHaveKey('file', 'assets/app-def456.css')
            ->toHaveKey('hash', 'def456');
    });

    it('does not create duplicate files without hash', function () {
        File::ensureDirectoryExists($this->testBuildDir);
        File::ensureDirectoryExists($this->testBuildDir . '/assets');
        
        File::put($this->testBuildDir . '/app-abc123.js', '// JS content');
        File::put($this->testBuildDir . '/assets/app-def456.css', '/* CSS content */');
        
        $this->service->generateManifest($this->testBuildDir);
        
        // Should NOT create app.js and app.css without hash
        expect(File::exists($this->testBuildDir . '/app.js'))->toBeFalse();
        expect(File::exists($this->testBuildDir . '/app.css'))->toBeFalse();
    });

    it('extracts hash from filename correctly', function () {
        File::ensureDirectoryExists($this->testBuildDir);
        
        $testCases = [
            'app-abc123.js' => 'abc123',
            'app-def456xyz.js' => 'def456xyz',
            'chunk-a1b2c3.js' => 'a1b2c3',
        ];
        
        foreach ($testCases as $filename => $expectedHash) {
            File::put($this->testBuildDir . '/' . $filename, '// content');
            
            $this->service->generateManifest($this->testBuildDir);
            
            $manifest = json_decode(File::get($this->testBuildDir . '/manifest.json'), true);
            
            if (str_starts_with($filename, 'app-')) {
                expect($manifest['app.js']['hash'])->toBe($expectedHash);
            }
            
            File::delete($this->testBuildDir . '/' . $filename);
        }
    });

    it('returns hashed asset URL from manifest', function () {
        File::ensureDirectoryExists($this->testBuildDir);
        
        File::put($this->testBuildDir . '/app-abc123.js', '// JS content');
        
        $this->service->generateManifest($this->testBuildDir);
        
        $url = $this->service->getAssetUrl('app.js');
        
        expect($url)
            ->toContain('app-abc123.js')
            ->not->toBe('/build/app.js');
    });

    it('handles missing manifest gracefully', function () {
        $url = $this->service->getAssetUrl('app.js');
        
        expect($url)->toBeString();
    });
});
