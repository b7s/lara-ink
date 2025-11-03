<?php

declare(strict_types=1);

namespace B7s\LaraInk\Console\Commands;

use B7s\LaraInk\Services\BuildService;
use Illuminate\Console\Command;

final class BuildSelectiveCommand extends Command
{
    protected $signature = 'lara-ink:build-selective {file : Path to the changed file}';

    protected $description = 'Build only pages affected by the changed file';

    public function __construct(
        private readonly BuildService $buildService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->components->error("File not found: {$file}");
            $this->line("  <fg=yellow>Checked path:</> {$file}");
            $this->line("  <fg=yellow>Current dir:</> " . getcwd());
            $this->line("  <fg=yellow>Base path:</> " . base_path());
            return self::FAILURE;
        }

        try {
            $result = $this->buildService->buildSelective($file);

            if ($result['success']) {
                $this->components->success($result['message']);
                $this->line("  <fg=green>{$result['pages']}</> <fg=green>page(s) compiled</>");
                return self::SUCCESS;
            }

            $this->components->error('Build failed');
            $this->line("  <fg=red>{$result['message']}</>");
            $this->line("  <fg=yellow>Type:</> {$result['type']}");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->components->error('Build exception');
            $this->line("  <fg=red>{$e->getMessage()}</>");
            $this->line("  <fg=yellow>File:</> {$e->getFile()}:{$e->getLine()}");
            return self::FAILURE;
        }
    }
}
