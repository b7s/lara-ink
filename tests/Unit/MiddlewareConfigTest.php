<?php

declare(strict_types=1);

use B7s\LaraInk\DTOs\PageConfig;
use B7s\LaraInk\Services\DslParserService;

test('middleware can be configured as string', function () {
    $content = <<<'PHP'
<?php
ink_make()
    ->middleware('admin');
?>
PHP;

    $parser = new DslParserService();
    $reflection = new ReflectionClass($parser);
    $method = $reflection->getMethod('extractConfig');
    $method->setAccessible(true);
    
    $config = $method->invoke($parser, $content);
    
    expect($config)->toBeInstanceOf(PageConfig::class);
    expect($config->middleware)->toBe(['admin']);
});

test('middleware can be configured as array', function () {
    $content = <<<'PHP'
<?php
ink_make()
    ->middleware(['auth', 'verified', 'role:admin']);
?>
PHP;

    $parser = new DslParserService();
    $reflection = new ReflectionClass($parser);
    $method = $reflection->getMethod('extractConfig');
    $method->setAccessible(true);
    
    $config = $method->invoke($parser, $content);
    
    expect($config)->toBeInstanceOf(PageConfig::class);
    expect($config->middleware)->toBe(['auth', 'verified', 'role:admin']);
});

test('middleware returns null when not configured', function () {
    $content = <<<'PHP'
<?php
ink_make()
    ->title('Test Page');
?>
PHP;

    $parser = new DslParserService();
    $reflection = new ReflectionClass($parser);
    $method = $reflection->getMethod('extractConfig');
    $method->setAccessible(true);
    
    $config = $method->invoke($parser, $content);
    
    expect($config)->toBeInstanceOf(PageConfig::class);
    expect($config->middleware)->toBeNull();
});

test('requiresAuth returns true when middleware is set', function () {
    $config = new PageConfig(
        middleware: ['admin']
    );
    
    expect($config->requiresAuth())->toBeTrue();
});

test('requiresAuth returns true when auth is true', function () {
    $config = new PageConfig(
        auth: true
    );
    
    expect($config->requiresAuth())->toBeTrue();
});

test('requiresAuth returns false when neither auth nor middleware is set', function () {
    $config = new PageConfig();
    
    expect($config->requiresAuth())->toBeFalse();
});
