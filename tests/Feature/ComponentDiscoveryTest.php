<?php

declare(strict_types=1);

namespace B7s\LaraInk\Tests\Feature;

use B7s\LaraInk\Services\ComponentService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ComponentDiscoveryTest extends TestCase
{
    public function test_discovers_components_in_correct_path(): void
    {
        $service = app(ComponentService::class);
        
        // Get the expected path
        $expectedPath = ink_resource_path('components');
        
        // Create a test component
        if (!File::exists($expectedPath)) {
            File::makeDirectory($expectedPath, 0755, true);
        }
        
        File::put($expectedPath . '/test-component.php', '<div>Test Component</div>');
        
        $components = $service->discoverComponents();
        
        $this->assertArrayHasKey('test-component', $components);
        
        // Cleanup
        File::delete($expectedPath . '/test-component.php');
    }
    
    public function test_discovers_nested_components(): void
    {
        $service = app(ComponentService::class);
        
        $expectedPath = ink_resource_path('components');
        
        if (!File::exists($expectedPath . '/subfolder')) {
            File::makeDirectory($expectedPath . '/subfolder', 0755, true);
        }
        
        File::put($expectedPath . '/subfolder/nested.php', '<div>Nested Component</div>');
        
        $components = $service->discoverComponents();
        
        $this->assertArrayHasKey('subfolder.nested', $components);
        
        // Cleanup
        File::delete($expectedPath . '/subfolder/nested.php');
        File::deleteDirectory($expectedPath . '/subfolder');
    }
}
