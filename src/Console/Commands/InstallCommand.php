<?php

declare(strict_types=1);

namespace B7s\LaraInk\Console\Commands;

use B7s\LaraInk\Support\InstallScaffolder;
use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'lara-ink:install';

    protected $description = 'Install LaraInk scaffolding and supporting assets.';

    public function __construct(
        private readonly InstallScaffolder $scaffolder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $basePath = base_path();

        $this->components->info('Preparing LaraInk scaffolding...');

        try {
            $this->scaffolder->scaffold(
                $basePath,
                function (string $message): void {
                    $this->components->info($message);
                }
            );

            $this->components->success('LaraInk scaffolding finished successfully.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->components->error('LaraInk scaffolding failed: ' . $exception->getMessage());

            report($exception);

            return self::FAILURE;
        }
    }
}
