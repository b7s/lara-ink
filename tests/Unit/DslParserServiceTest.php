<?php

declare(strict_types=1);

use B7s\LaraInk\Services\DslParserService;

test('parses DSL config correctly', function () {
    $service = app(DslParserService::class);
    
    $content = <<<'PHP'
<?php
ink_make()
    ->cache(600)
    ->layout('dashboard/app')
    ->title('Test Page')
    ->auth(true);
?>
PHP;

    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, $content);

    $parsed = $service->parse($tempFile);

    expect($parsed->config->getCacheTtl())->toBe(600);
    expect($parsed->config->layout)->toBe('dashboard/app');
    expect($parsed->config->title)->toBe('Test Page');
    expect($parsed->config->auth)->toBeTrue();

    unlink($tempFile);
});

test('extracts HTML section', function () {
    $service = app(DslParserService::class);
    
    $content = <<<'PHP'
<?php
ink_make();

<<<HTML
<h1>Hello World</h1>
HTML;
PHP;

    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, $content);

    $parsed = $service->parse($tempFile);

    expect($parsed->html)->toContain('Hello World');

    unlink($tempFile);
});

test('extracts translations', function () {
    $service = app(DslParserService::class);
    
    $content = <<<'PHP'
<?php
ink_make();

<<<HTML
<h1>{{ trans('app.title') }}</h1>
<p>{{ trans('app.description') }}</p>
HTML;
PHP;

    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, $content);

    $parsed = $service->parse($tempFile);

    expect($parsed->translations)->toContain('app.title');
    expect($parsed->translations)->toContain('app.description');

    unlink($tempFile);
});

test('extracts SEO config', function () {
    $service = app(DslParserService::class);
    
    $content = <<<'PHP'
<?php
ink_make()
    ->seo('Test SEO Title', 'Test description', 'test,keywords', '/image.jpg');
?>
PHP;

    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, $content);

    $parsed = $service->parse($tempFile);

    $seo = $parsed->config->getSeoConfig();
    expect($seo)->not->toBeNull();
    expect($seo['title'])->toBe('Test SEO Title');
    expect($seo['description'])->toBe('Test description');

    unlink($tempFile);
});
