<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Mock ink_config function globally
if (!function_exists('ink_config')) {
    function ink_config(string $key, mixed $default = null): mixed
    {
        return match($key) {
            'cache.ttl' => 300,
            'default_layout' => 'app',
            'auth.route.prefix' => '/api/ink',
            'api_base_url' => '',
            'output.build_dir' => 'public/build',
            'output.pages_dir' => 'public/pages',
            'output.dir' => 'public',
            default => $default,
        };
    }
}

// Mock App facade
if (!class_exists('Illuminate\Support\Facades\App')) {
    class MockApp {
        public static function basePath(string $path = ''): string
        {
            return sys_get_temp_dir() . '/lara-ink-test/' . ltrim($path, '/');
        }
        
        public static function langPath(): string
        {
            return sys_get_temp_dir() . '/lara-ink-test/lang';
        }
    }
    
    class_alias('MockApp', 'Illuminate\Support\Facades\App');
}

// Mock Route facade
if (!class_exists('Illuminate\Support\Facades\Route')) {
    class MockRoute {
        public static function has(string $name): bool
        {
            return false;
        }
        
        public static function getRoutes()
        {
            return new class {
                public function getByName(string $name): ?\Illuminate\Routing\Route
                {
                    return null;
                }
            };
        }
    }
    
    class_alias('MockRoute', 'Illuminate\Support\Facades\Route');
}

// Mock File facade
if (!class_exists('Illuminate\Support\Facades\File')) {
    class MockFile {
        public static function exists(string $path): bool
        {
            return file_exists($path);
        }
        
        public static function get(string $path): string
        {
            return file_get_contents($path) ?: '';
        }
        
        public static function put(string $path, string $content): bool
        {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            return file_put_contents($path, $content) !== false;
        }
        
        public static function ensureDirectoryExists(string $path): void
        {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        public static function directories(string $path): array
        {
            if (!is_dir($path)) {
                return [];
            }
            
            $dirs = [];
            foreach (scandir($path) as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($path . '/' . $item)) {
                    $dirs[] = $path . '/' . $item;
                }
            }
            return $dirs;
        }
        
        public static function files(string $path): array
        {
            if (!is_dir($path)) {
                return [];
            }
            
            $files = [];
            foreach (scandir($path) as $item) {
                if ($item !== '.' && $item !== '..' && is_file($path . '/' . $item)) {
                    $files[] = new \SplFileInfo($path . '/' . $item);
                }
            }
            return $files;
        }
        
        public static function allFiles(string $path): array
        {
            return self::files($path);
        }
        
        public static function cleanDirectory(string $path): void
        {
            if (!is_dir($path)) {
                return;
            }
            
            foreach (scandir($path) as $item) {
                if ($item !== '.' && $item !== '..') {
                    $fullPath = $path . '/' . $item;
                    if (is_dir($fullPath)) {
                        self::cleanDirectory($fullPath);
                        rmdir($fullPath);
                    } else {
                        unlink($fullPath);
                    }
                }
            }
        }
        
        public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = true): void
        {
            if (!is_dir($path)) {
                mkdir($path, $mode, $recursive);
            }
        }
    }
    
    class_alias('MockFile', 'Illuminate\Support\Facades\File');
}

// Create temp directory for tests
$testDir = sys_get_temp_dir() . '/lara-ink-test';
if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}
