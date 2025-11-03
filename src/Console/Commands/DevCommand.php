<?php

declare(strict_types=1);

namespace B7s\LaraInk\Console\Commands;

use B7s\LaraInk\Services\BuildService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class DevCommand extends Command
{
    protected $signature = 'lara-ink:dev {--port=5173 : Vite dev server port}';

    protected $description = 'Start LaraInk development server with hot reload';

    public function __construct(
        private readonly BuildService $buildService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('LaraInk - Starting development server');
        $this->newLine();

        // Initial build
        $this->line('  <fg=gray>Preparing build pipeline...</>');
        $result = $this->buildService->build();

        if (!$result['success']) {
            $this->components->error('Initial build failed');
            $this->line("  <fg=red>{$result['message']}</>");
            return self::FAILURE;
        }

        $this->components->success('Initial build completed');
        $this->line("  <fg=green>{$result['pages']}</> <fg=green>page(s) compiled</>");
        $this->newLine();

        // Start file watcher
        $this->components->info('Watching for changes in resources/lara-ink/...');
        $this->line('  <fg=yellow>Press Ctrl+C to stop</>');;
        $this->newLine();

        $watchPath = base_path('resources/lara-ink');
        $lastBuildTime = time();
        $debounceSeconds = 1; // Debounce to avoid multiple builds

        while (true) {
            clearstatcache();
            
            $changedFile = $this->getChangedFile($watchPath, $lastBuildTime);
            
            if ($changedFile !== null) {
                $relativePath = str_replace(base_path() . '/', '', $changedFile);
                $this->line("  <fg=cyan>Changes detected:</> <fg=white>{$relativePath}</>");
                
                $result = $this->buildService->buildSelective($changedFile);
                
                if ($result['success']) {
                    $typeColor = match($result['type']) {
                        'page' => 'blue',
                        'layout' => 'magenta',
                        'component' => 'yellow',
                        default => 'green'
                    };
                    $this->line("  <fg=green>✓</> Rebuilt <fg={$typeColor}>{$result['type']}</> - {$result['pages']} page(s) at " . date('H:i:s'));
                } else {
                    $this->line("  <fg=red>✗</> Build failed: {$result['message']}");
                }
                
                $lastBuildTime = time();
            }
            
            usleep(500000); // Check every 0.5 seconds
        }

        return self::SUCCESS;
    }

    private function getChangedFile(string $path, int $since): ?string
    {
        if (!is_dir($path)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() > $since) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
