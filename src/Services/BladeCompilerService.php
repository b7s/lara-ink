<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

final class BladeCompilerService
{
    /**
     * Compile Blade directives to Alpine.js syntax
     */
    public function compile(string $blade): string
    {
        $compiled = $blade;

        $compiled = $this->compileEchos($compiled);
        $compiled = $this->compileIf($compiled);
        $compiled = $this->compileForeach($compiled);
        $compiled = $this->compileFor($compiled);
        $compiled = $this->compileWhile($compiled);
        $compiled = $this->compileUnless($compiled);
        $compiled = $this->compileEmpty($compiled);
        $compiled = $this->compileIsset($compiled);
        $compiled = $this->compileSwitch($compiled);
        $compiled = $this->compilePhp($compiled);
        $compiled = $this->compileJson($compiled);

        return $compiled;
    }

    private function compileEchos(string $content): string
    {
        // {{ $variable }} → <span x-text="variable"></span>
        $content = preg_replace(
            '/\{\{\s*\$(\w+)\s*\}\}/',
            '<span x-text="$1"></span>',
            $content
        );

        // {{ $variable->property }} → <span x-text="variable.property"></span>
        $content = preg_replace(
            '/\{\{\s*\$(\w+)->(\w+)\s*\}\}/',
            '<span x-text="$1.$2"></span>',
            $content
        );

        // {!! $html !!} → <span x-html="html"></span>
        $content = preg_replace(
            '/\{!!\s*\$(\w+)\s*!!\}/',
            '<span x-html="$1"></span>',
            $content
        );

        return $content;
    }

    private function compileIf(string $content): string
    {
        // @if($condition) → <template x-if="condition">
        $content = preg_replace(
            '/@if\s*\(\s*\$(\w+)\s*\)/',
            '<template x-if="$1">',
            $content
        );

        // @elseif($condition) → </template><template x-if="condition">
        $content = preg_replace(
            '/@elseif\s*\(\s*\$(\w+)\s*\)/',
            '</template><template x-if="$1">',
            $content
        );

        // @else → </template><template x-if="true">
        $content = preg_replace(
            '/@else\b/',
            '</template><template x-if="true">',
            $content
        );

        // @endif → </template>
        $content = preg_replace(
            '/@endif\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileForeach(string $content): string
    {
        // @foreach($items as $item) → <template x-for="item in items">
        $content = preg_replace(
            '/@foreach\s*\(\s*\$(\w+)\s+as\s+\$(\w+)\s*\)/',
            '<template x-for="$2 in $1">',
            $content
        );

        // @foreach($items as $key => $value) → <template x-for="(value, key) in items">
        $content = preg_replace(
            '/@foreach\s*\(\s*\$(\w+)\s+as\s+\$(\w+)\s*=>\s*\$(\w+)\s*\)/',
            '<template x-for="($3, $2) in $1">',
            $content
        );

        // @endforeach → </template>
        $content = preg_replace(
            '/@endforeach\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileFor(string $content): string
    {
        // @for($i = 0; $i < 10; $i++) → <template x-for="i in Array.from({length: 10}, (_, i) => i)">
        // Simplified: just wrap in template for now
        $content = preg_replace(
            '/@for\s*\([^)]+\)/',
            '<template x-data="{ forLoop: true }">',
            $content
        );

        // @endfor → </template>
        $content = preg_replace(
            '/@endfor\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileWhile(string $content): string
    {
        // @while($condition) → <template x-if="condition">
        $content = preg_replace(
            '/@while\s*\(\s*\$(\w+)\s*\)/',
            '<template x-if="$1">',
            $content
        );

        // @endwhile → </template>
        $content = preg_replace(
            '/@endwhile\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileUnless(string $content): string
    {
        // @unless($condition) → <template x-if="!condition">
        $content = preg_replace(
            '/@unless\s*\(\s*\$(\w+)\s*\)/',
            '<template x-if="!$1">',
            $content
        );

        // @endunless → </template>
        $content = preg_replace(
            '/@endunless\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileEmpty(string $content): string
    {
        // @empty($variable) → <template x-if="!variable || variable.length === 0">
        $content = preg_replace(
            '/@empty\s*\(\s*\$(\w+)\s*\)/',
            '<template x-if="!$1 || (Array.isArray($1) && $1.length === 0)">',
            $content
        );

        // @endempty → </template>
        $content = preg_replace(
            '/@endempty\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileIsset(string $content): string
    {
        // @isset($variable) → <template x-if="typeof variable !== 'undefined'">
        $content = preg_replace(
            '/@isset\s*\(\s*\$(\w+)\s*\)/',
            '<template x-if="typeof $1 !== \'undefined\'">',
            $content
        );

        // @endisset → </template>
        $content = preg_replace(
            '/@endisset\b/',
            '</template>',
            $content
        );

        return $content;
    }

    private function compileSwitch(string $content): string
    {
        // @switch($variable) → <div x-data="{ switchVar: variable }">
        $content = preg_replace(
            '/@switch\s*\(\s*\$(\w+)\s*\)/',
            '<div x-data="{ switchVar: $1 }">',
            $content
        );

        // @case($value) → <template x-if="switchVar === value">
        $content = preg_replace(
            '/@case\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '<template x-if="switchVar === \'$1\'">',
            $content
        );

        $content = preg_replace(
            '/@case\s*\(\s*(\d+)\s*\)/',
            '<template x-if="switchVar === $1">',
            $content
        );

        // @break → </template>
        $content = preg_replace(
            '/@break\b/',
            '</template>',
            $content
        );

        // @default → <template x-if="true">
        $content = preg_replace(
            '/@default\b/',
            '<template x-if="true">',
            $content
        );

        // @endswitch → </div>
        $content = preg_replace(
            '/@endswitch\b/',
            '</div>',
            $content
        );

        return $content;
    }

    private function compilePhp(string $content): string
    {
        // Remove @php...@endphp blocks (not supported in frontend)
        $content = preg_replace(
            '/@php\b.*?@endphp\b/s',
            '<!-- PHP block removed -->',
            $content
        );

        return $content;
    }

    private function compileJson(string $content): string
    {
        // @json($variable) → <script>var data = JSON.stringify(variable);</script>
        $content = preg_replace(
            '/@json\s*\(\s*\$(\w+)\s*\)/',
            '<script>window.$1 = JSON.parse(\'{{ json_encode(\$$1) }}\');</script>',
            $content
        );

        return $content;
    }
}
