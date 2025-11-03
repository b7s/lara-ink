<?php

declare(strict_types=1);

use B7s\LaraInk\Services\DslParserService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->pagesPath = ink_resource_path('pages');
    File::ensureDirectoryExists($this->pagesPath);
});

afterEach(function () {
    if (File::exists($this->pagesPath)) {
        File::deleteDirectory($this->pagesPath);
    }
});

test('accepts .php files', function () {
    $testFile = $this->pagesPath . '/test.php';
    File::put($testFile, '<?php ink_make(); ?>');
    
    $parser = app(DslParserService::class);
    $parsed = $parser->parse($testFile);
    
    expect($parsed->slug)->toBe('/test');
});

test('accepts .blade.php files', function () {
    $testFile = $this->pagesPath . '/test.blade.php';
    File::put($testFile, '<?php ink_make(); ?>');
    
    $parser = app(DslParserService::class);
    $parsed = $parser->parse($testFile);
    
    expect($parsed->slug)->toBe('/test');
});

test('extracts route parameters from filename with [slug].[id] pattern', function () {
    $testFile = $this->pagesPath . '/product.[slug].[id].php';
    File::put($testFile, '<?php ink_make(); ?>');
    
    $parser = app(DslParserService::class);
    $parsed = $parser->parse($testFile);
    
    expect($parsed->slug)->toBe('/product.[slug].[id]');
    expect($parsed->params)->toHaveKey('slug');
    expect($parsed->params)->toHaveKey('id');
});

test('extracts route parameters from nested path', function () {
    $nestedPath = $this->pagesPath . '/admin';
    File::ensureDirectoryExists($nestedPath);
    
    $testFile = $nestedPath . '/user.[id].php';
    File::put($testFile, '<?php ink_make(); ?>');
    
    $parser = app(DslParserService::class);
    $parsed = $parser->parse($testFile);
    
    expect($parsed->slug)->toBe('/admin/user.[id]');
    expect($parsed->params)->toHaveKey('id');
});

test('handles multiple parameters in filename', function () {
    $testFile = $this->pagesPath . '/post.[category].[slug].[id].blade.php';
    File::put($testFile, '<?php ink_make(); ?>');
    
    $parser = app(DslParserService::class);
    $parsed = $parser->parse($testFile);
    
    expect($parsed->slug)->toBe('/post.[category].[slug].[id]');
    expect($parsed->params)->toHaveKey('category');
    expect($parsed->params)->toHaveKey('slug');
    expect($parsed->params)->toHaveKey('id');
});
