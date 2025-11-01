<?php

declare(strict_types=1);

namespace B7s\LaraInk\Console\Commands;

use B7s\LaraInk\Services\BuildService;
use Illuminate\Console\Command;
use function Termwind\render;

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
        render(<<<HTML
<div class="mb-1">
    <span class="px-2 py-1 bg-purple-600 text-white font-bold uppercase">LaraInk</span>
    <div class="mt-1 text-slate-400">[Building SPA bundle]</div>
</div>
HTML);

        render('<div class="px-2 py-1 bg-slate-800 text-slate-200">Preparing build pipeline...</div>');

        $result = $this->buildService->build();

        if ($result['success']) {
            render(<<<HTML
<div class="mt-1">
    <div class="px-2 py-1 bg-green-600 text-white font-bold">✅ Build completed</div>
    <div class="px-2 py-1 text-green-200">{$result['message']}</div>
    <div class="px-2 py-1 text-green-100"><span class="font-bold">{$result['pages']}</span> page(s) compiled</div>
</div>
HTML);

            return self::SUCCESS;
        }

        render(<<<HTML
<div class="mt-1">
    <div class="px-2 py-1 bg-red-600 text-white font-bold">⛔ Build failed</div>
    <div class="px-2 py-1 text-red-200">{$result['message']}</div>
</div>
HTML);

        return self::FAILURE;
    }
}
