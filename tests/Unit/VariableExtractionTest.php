<?php

declare(strict_types=1);

use B7s\LaraInk\Services\DslParserService;
use Illuminate\Support\Facades\App;

test('DslParserService extracts simple string variables', function () {
    $parserService = app(DslParserService::class);
    $tempFile = tempnam(sys_get_temp_dir(), 'lara_ink_test_');
    
    $content = <<<'PHP'
<?php
ink_make()
    ->title('Test Page');

$message = 'Hello World';
?>

<div>{{ $message }}</div>
PHP;
    
    file_put_contents($tempFile, $content);
    
    try {
        $parsed = $parserService->parse($tempFile);
        
        expect($parsed->variables)->toHaveKey('message')
            ->and($parsed->variables['message']->name)->toBe('message')
            ->and($parsed->variables['message']->value)->toBe('Hello World')
            ->and($parsed->variables['message']->type)->toBe('string');
    } finally {
        unlink($tempFile);
    }
});

test('DslParserService extracts array variables', function () {
    $parserService = app(DslParserService::class);
    $tempFile = tempnam(sys_get_temp_dir(), 'lara_ink_test_');
    
    $content = <<<'PHP'
<?php
ink_make()
    ->title('Test Page');

$users = [
    ['name' => 'John Doe'],
    ['name' => 'Jane Smith'],
];
?>

<div>Test</div>
PHP;
    
    file_put_contents($tempFile, $content);
    
    try {
        $parsed = $parserService->parse($tempFile);
        
        expect($parsed->variables)->toHaveKey('users')
            ->and($parsed->variables['users']->name)->toBe('users')
            ->and($parsed->variables['users']->type)->toBe('array')
            ->and($parsed->variables['users']->value)->toBeArray()
            ->and($parsed->variables['users']->value)->toHaveCount(2);
    } finally {
        unlink($tempFile);
    }
});

test('DslParserService extracts multiple variables with different types', function () {
    $parserService = app(DslParserService::class);
    $tempFile = tempnam(sys_get_temp_dir(), 'lara_ink_test_');
    
    $content = <<<'PHP'
<?php
ink_make()
    ->title('Test Page');

$message = 'Hello';
$count = 42;
$price = 19.99;
$isActive = true;
$items = ['a', 'b', 'c'];
?>

<div>Test</div>
PHP;
    
    file_put_contents($tempFile, $content);
    
    try {
        $parsed = $parserService->parse($tempFile);
        
        expect($parsed->variables)->toHaveKey('message')
            ->and($parsed->variables)->toHaveKey('count')
            ->and($parsed->variables)->toHaveKey('price')
            ->and($parsed->variables)->toHaveKey('isActive')
            ->and($parsed->variables)->toHaveKey('items')
            ->and($parsed->variables['message']->type)->toBe('string')
            ->and($parsed->variables['count']->type)->toBe('int')
            ->and($parsed->variables['price']->type)->toBe('float')
            ->and($parsed->variables['isActive']->type)->toBe('bool')
            ->and($parsed->variables['items']->type)->toBe('array');
    } finally {
        unlink($tempFile);
    }
});

test('DslParserService generates unique Alpine variable names', function () {
    $parserService = app(DslParserService::class);
    $tempFile = tempnam(sys_get_temp_dir(), 'lara_ink_test_');
    
    $content = <<<'PHP'
<?php
ink_make()
    ->title('Test Page');

$users = ['John', 'Jane'];
?>

<div>Test</div>
PHP;
    
    file_put_contents($tempFile, $content);
    
    try {
        $parsed = $parserService->parse($tempFile);
        
        expect($parsed->variables['users']->alpineVarName)
            ->toStartWith('var_users_')
            ->and(strlen($parsed->variables['users']->alpineVarName))->toBeGreaterThan(10);
    } finally {
        unlink($tempFile);
    }
});

test('DslParserService throws error on invalid PHP syntax', function () {
    $parserService = app(DslParserService::class);
    $tempFile = tempnam(sys_get_temp_dir(), 'lara_ink_test_');
    
    $content = <<<'PHP'
<?php
ink_make()
    ->title('Test Page');

$invalid = [
    'unclosed array
?>

<div>Test</div>
PHP;
    
    file_put_contents($tempFile, $content);
    
    try {
        $parserService->parse($tempFile);
    } finally {
        unlink($tempFile);
    }
})->throws(ParseError::class);
