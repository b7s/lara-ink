<?php

declare(strict_types=1);

use B7s\LaraInk\Services\CacheService;

test('registers page cache', function () {
    $service = new CacheService();
    
    $service->registerPageCache('/products', 600);
    
    expect($service->getPageCacheTtl('/products'))->toBe(600);
    expect($service->shouldCache('/products'))->toBeTrue();
});

test('returns null for non-cached page', function () {
    $service = new CacheService();
    
    expect($service->getPageCacheTtl('/non-existent'))->toBeNull();
});

test('generates cache manifest', function () {
    $service = new CacheService();
    
    $service->registerPageCache('/products', 600);
    $service->registerPageCache('/about', 300);
    
    $manifest = json_decode($service->generateCacheManifest(), true);
    
    expect($manifest)->toHaveKey('/products');
    expect($manifest['/products']['ttl'])->toBe(600);
    expect($manifest['/about']['ttl'])->toBe(300);
});
