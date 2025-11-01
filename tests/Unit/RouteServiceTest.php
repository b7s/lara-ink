<?php

declare(strict_types=1);

use B7s\LaraInk\Services\RouteService;

test('registers page routes', function () {
    $service = new RouteService();
    
    $service->registerPageRoute('/products', '/products', []);
    
    $routes = $service->getPageRoutes();
    
    expect($routes)->toHaveKey('/products');
    expect($routes['/products']['pattern'])->toBe('/products');
});

test('resolves page route with parameters', function () {
    $service = new RouteService();
    
    $service->registerPageRoute('/product/[slug].[id]', '/product/[slug].[id]', ['slug', 'id']);
    
    $route = $service->resolve('/product/[slug].[id]', ['slug' => 'test-product', 'id' => 42]);
    
    expect($route->url)->toBe('/product/test-product.42');
    expect($route->method)->toBe('GET');
});

test('throws exception for non-existent route', function () {
    $service = new RouteService();
    
    expect(fn() => $service->resolve('/non-existent'))->toThrow(RuntimeException::class);
});
