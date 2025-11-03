<?php

declare(strict_types=1);

namespace B7s\LaraInk\Console\Commands;

use B7s\LaraInk\Services\BuildService;
use Illuminate\Console\Command;

final class BuildCommand extends Command
{
    protected $signature = 'lara-ink:build';

    protected $description = 'Build LaraInk SPA from DSL files';

    public function __construct(
        private readonly BuildService $buildService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('LaraInk - Building SPA bundle');
        $this->newLine();
        
        $this->line('  <fg=gray>Preparing build pipeline...</>');

        $result = $this->buildService->build();

        if ($result['success']) {
            $this->newLine();
            $this->components->success('Build completed');
            $this->line("  <fg=green>{$result['message']}</>");
            $this->line("  <fg=green;options=bold>{$result['pages']}</> <fg=green>page(s) compiled</>");
            $this->newLine();

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->error('Build failed');
        $this->line("  <fg=red>{$result['message']}</>");
        $this->newLine();

        return self::FAILURE;
    }
}
