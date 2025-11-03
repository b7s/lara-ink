<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\PageVariable;
use B7s\LaraInk\DTOs\ParsedPage;

final class CompilerService
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly RouteService $routeService,
        private readonly BladeCompilerService $bladeCompiler,
        private readonly ComponentService $componentService,
    ) {}

    public function compileHtml(ParsedPage $page): string
    {
        $html = $page->html;
        $context = $this->extractVariableContext($page);

        // Process components FIRST (before Blade) so they can be included in the compilation
        $html = $this->processComponents($html, $page->id, $context);

        // Transform translations before compiling Blade directives
        $html = $this->transformTranslations($html);

        // Compile Blade directives (loops, conditionals, etc.)
        $html = $this->bladeCompiler->compile($html);

        // Substitute variables after compilation so attributes are already normalized
        $html = $this->transformVariables($html, $page);

        // Replace PHP variable references with Alpine variable names after compilation
        $html = $this->replacePhpVariablesWithAlpine($html, $page);

        return $html;
    }

    /**
     * Process component tags (@include and <x-*>)
     */
    private function processComponents(string $html, string $parentId, array $context): string
    {
        // Process @include directives
        $html = $this->processIncludeDirectives($html, $parentId, $context);

        // Process <x-*> component tags
        $html = $this->processComponentTags($html, $parentId, $context);

        return $html;
    }

    private function processIncludeDirectives(string $html, string $parentId, array $context): string
    {
        return preg_replace_callback(
            '/@include\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(\[.*?\]))?\s*\)/',
            function (array $matches) use ($parentId, $context): string {
                $componentName = $matches[1];
                $propsArray = $matches[2] ?? '[]';

                $componentContent = $this->componentService->loadComponent($componentName);

                if ($componentContent === null) {
                    return "<!-- Component not found: {$componentName} -->";
                }

                $componentId = $this->componentService->generateComponentId($componentName, $parentId);

                // Parse props from array syntax
                $props = $this->parsePropsFromArray($propsArray, $context);
                $componentContext = $this->mergeComponentContext($context, $props);

                // Recursively process nested components
                $componentContent = $this->processComponents($componentContent, $componentId, $componentContext);
                $componentContent = $this->transformTranslations($componentContent);
                $componentContent = $this->bladeCompiler->compile($componentContent);

                return $this->componentService->compileComponent($componentContent, $props, $componentId, false);
            },
            $html
        );
    }

    private function processComponentTags(string $html, string $parentId, array $context): string
    {
        $iterationLimit = 10;
        $iteration = 0;

        // Keep processing until no more component tags are found (for nested components)
        do {
            $hasComponents = false;
            $iteration++;

            // Process paired tags first: <x-name>...</x-name>
            $newHtml = preg_replace_callback(
                '/<x-([A-Za-z0-9\-:\._]+)((?:\s+(?:[^"\'>]|"[^"]*"|\'[^\']*\')*)?)>(.*?)<\/x-\1>/s',
                function (array $matches) use ($parentId, $context, &$hasComponents): string {
                    $hasComponents = true;
                    return $this->renderComponentTag(
                        componentName: $matches[1],
                        attributes: $matches[2],
                        slotContent: $matches[3],
                        parentId: $parentId,
                        isSelfClosing: false,
                        context: $context
                    );
                },
                $html
            );

            // Then process self-closing tags: <x-name />
            $newHtml = preg_replace_callback(
                '/<x-([A-Za-z0-9\-:\._]+)((?:\s+(?:[^"\'\/>]|"[^"]*"|\'[^\']*\')*)?)\/>/s',
                function (array $matches) use ($parentId, $context, &$hasComponents): string {
                    $hasComponents = true;
                    return $this->renderComponentTag(
                        componentName: $matches[1],
                        attributes: $matches[2],
                        slotContent: '',
                        parentId: $parentId,
                        isSelfClosing: true,
                        context: $context
                    );
                },
                $newHtml
            );

            $html = $newHtml;
        } while ($hasComponents && $iteration < $iterationLimit);

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePropsFromArray(string $arrayString, array $context): array
    {
        $trimmed = trim($arrayString);

        if ($trimmed === '') {
            return [];
        }

        try {
            $evaluated = $this->evaluatePhpExpression($trimmed, $context);

            if (is_array($evaluated)) {
                return $evaluated;
            }
        } catch (\Throwable $e) {
            // Silent fallback to empty props when evaluation fails
        }

        return [];
    }

    private function renderComponentTag(
        string $componentName,
        string $attributes,
        string $slotContent,
        string $parentId,
        bool $isSelfClosing,
        array $context
    ): string {
        $candidates = $this->buildComponentNameCandidates($componentName);
        $normalizedName = null;
        $componentContent = null;

        foreach ($candidates as $candidate) {
            $content = $this->componentService->loadComponent($candidate);

            if ($content !== null) {
                $normalizedName = $candidate;
                $componentContent = $content;
                break;
            }
        }

        $discovered = [];

        if ($componentContent === null) {
            $discovered = $this->componentService->discoverComponents();

            foreach ($candidates as $candidate) {
                $content = $this->componentService->loadComponent($candidate);

                if ($content !== null) {
                    $normalizedName = $candidate;
                    $componentContent = $content;
                    break;
                }
            }
        }

        if ($componentContent === null || $normalizedName === null) {
            $requested = implode("', '", $candidates);
            $availableComponents = $discovered === [] ? 'none' : implode(', ', array_keys($discovered));

            return "<!-- Component not found. Tried: ['{$requested}'] | Available: [{$availableComponents}] | Path: " . ink_resource_path('components') . " -->";
        }

        $componentId = $this->componentService->generateComponentId($normalizedName, $parentId);
        $parsedAttrs = $this->componentService->parseComponentAttributes($attributes);
        $preparedProps = $this->prepareComponentProps($parsedAttrs['props'], $context);
        $componentContext = $this->mergeComponentContext($context, $preparedProps);

        if (!$isSelfClosing && trim($slotContent) !== '') {
            $componentContent = str_replace(['{{ $slot }}', '{!! $slot !!}'], $slotContent, $componentContent);
        }

        // Recursively process nested components
        $componentContent = $this->processComponents($componentContent, $componentId, $componentContext);
        $componentContent = $this->transformTranslations($componentContent);
        $componentContent = $this->bladeCompiler->compile($componentContent);

        return $this->componentService->compileComponent(
            $componentContent,
            $preparedProps,
            $componentId,
            $parsedAttrs['lazy']
        );
    }

    /**
     * @return array<int, string>
     */
    private function buildComponentNameCandidates(string $componentName): array
    {
        $candidates = [$componentName];
        $namespaceNormalized = str_replace('::', '.', $componentName);

        if ($namespaceNormalized !== $componentName) {
            $candidates[] = $namespaceNormalized;
        }

        $hyphenAsDot = str_replace('-', '.', $namespaceNormalized);

        if ($hyphenAsDot !== $namespaceNormalized) {
            $candidates[] = $hyphenAsDot;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param array<string, array{type: string, value: mixed}> $props
     * @return array<string, mixed>
     */
    private function prepareComponentProps(array $props, array $context): array
    {
        $prepared = [];

        foreach ($props as $name => $config) {
            switch ($config['type']) {
                case 'binding':
                    $prepared[$name] = $this->evaluateBindingValue($config['value'], $context);
                    break;
                case 'boolean':
                    $prepared[$name] = (bool) $config['value'];
                    break;
                default:
                    $prepared[$name] = $config['value'];
            }
        }

        return $prepared;
    }

    /**
     * @param array<string, mixed> $baseContext
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function mergeComponentContext(array $baseContext, array $props): array
    {
        $context = $baseContext;

        foreach ($props as $name => $value) {
            if (preg_match('/^[A-Za-z_][\w]*$/', $name)) {
                $context[$name] = $value;
            }
        }

        return $context;
    }

    private function evaluateBindingValue(string $expression, array $context): mixed
    {
        try {
            return $this->evaluatePhpExpression($expression, $context);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function transformVariables(string $html, ParsedPage $page): string
    {
        foreach ($page->variables as $variable) {
            // Replace {{ $varName }} with Alpine variable
            $html = preg_replace(
                '/\{\{\s*\$' . preg_quote($variable->name, '/') . '\s*\}\}/',
                '{{ ' . $variable->alpineVarName . ' }}',
                $html
            );

            // Replace $varName in directives
            $html = preg_replace(
                '/\$' . preg_quote($variable->name, '/') . '\b/',
                $variable->alpineVarName,
                $html
            );

            // Replace plain variable references inside Alpine attributes
            $html = preg_replace_callback(
                '/x-[\w:-]+="[^"]*"/',
                function (array $matches) use ($variable): string {
                    return preg_replace(
                        '/\b' . preg_quote($variable->name, '/') . '\b/',
                        $variable->alpineVarName,
                        $matches[0]
                    );
                },
                $html
            );

            $html = preg_replace_callback(
                "/x-[\\w:-]+='[^']*'/",
                function (array $matches) use ($variable): string {
                    return preg_replace(
                        '/\b' . preg_quote($variable->name, '/') . '\b/',
                        $variable->alpineVarName,
                        $matches[0]
                    );
                },
                $html
            );
        }

        return $html;
    }

    private function replacePhpVariablesWithAlpine(string $html, ParsedPage $page): string
    {
        foreach ($page->params as $param => $value) {
            $html = str_replace('$' . $param, "request().$param", $html);
        }

        return $html;
    }

    public function generateAlpineVariablesInit(ParsedPage $page): string
    {
        if ($page->variables === []) {
            return '';
        }

        $initCode = [];

        foreach ($page->variables as $variable) {
            try {
                $jsonValue = $variable->toJson();
                $initCode[] = "{$variable->alpineVarName}: {$jsonValue}";
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    "Error converting variable '\${$variable->name}' to JSON in file: {$page->filePath}\n" .
                        "Error: {$e->getMessage()}\n" .
                        "Make sure the variable contains only JSON-serializable data."
                );
            }
        }

        return implode(",\n", $initCode);
    }

    private function transformApiCalls(string $js): string
    {
        return preg_replace(
            '/@(\w+)\((.*?)\)/',
            'await lara_ink.newReq(\'$1\', $2)',
            $js
        );
    }

    public function compileCss(ParsedPage $page, array $variables): string
    {
        if (!isset($variables['styles']) || !is_array($variables['styles'])) {
            return '';
        }

        $cssOutput = [];

        foreach ($variables['styles'] as $style) {
            if (!is_array($style)) {
                continue;
            }

            $css = $style['css'] ?? '';
            $scoped = $style['scoped'] ?? false;
            $selector = $style['selector'] ?? $page->id;

            if ($css === '') {
                continue;
            }

            if ($scoped) {
                $prefix = '#' . $selector;
                $css = preg_replace_callback(
                    '/(^|\})\s*([^{}]+)\s*\{/',
                    static function (array $matches) use ($prefix) {
                        $selectors = array_map('trim', explode(',', $matches[2]));
                        $prefixed = array_map(
                            static fn(string $sel): string => $prefix . ' ' . $sel,
                            $selectors
                        );

                        return $matches[1] . ' ' . implode(', ', $prefixed) . ' {';
                    },
                    $css
                ) ?? $css;
            }

            $cssOutput[] = $css;
        }

        return implode("\n", $cssOutput);
    }

    private function transformTranslations(string $content): string
    {
        // First convert Blade echo statements directly into Alpine bindings
        $echoPatterns = [
            '/\{\{\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*\}\}/' => '<span x-text="lara_ink.trans(\'$1\')"></span>',
            '/\{\{\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*\}\}/' => '<span x-text="lara_ink.trans(\'$1\')"></span>',
            '/\{\{\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*\}\}/' => '<span x-text="lara_ink.trans(\'$1\')"></span>',
            '/\{!!\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*!!\}/' => '<span x-html="lara_ink.trans(\'$1\')"></span>',
            '/\{!!\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*!!\}/' => '<span x-html="lara_ink.trans(\'$1\')"></span>',
            '/\{!!\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*!!\}/' => '<span x-html="lara_ink.trans(\'$1\')"></span>',
        ];

        foreach ($echoPatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Replace any remaining translation helper calls with JS helpers
        $helperPatterns = [
            '/__\([\'\"]([^\'\"]+)[\'\"]\)/' => 'lara_ink.trans(\'$1\')',
            '/trans\([\'\"]([^\'\"]+)[\'\"]\)/' => 'lara_ink.trans(\'$1\')',
            '/@lang\([\'\"]([^\'\"]+)[\'\"]\)/' => 'lara_ink.trans(\'$1\')',
        ];

        foreach ($helperPatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Catch any remaining Blade echos using the JS helper
        $content = preg_replace(
            '/\{\{\s*lara_ink\.trans\(([^}]+)\)\s*\}\}/',
            '<span x-text="lara_ink.trans($1)"></span>',
            $content
        );

        return $content;
    }

    public function compileJs(ParsedPage $page): string
    {
        $js = $page->js;

        $js = $this->transformTranslations($js);
        $js = $this->transformApiCalls($js);
        $js = $this->injectRequestObject($js, $page);

        return $js;
    }

    private function injectRequestObject(string $js, ParsedPage $page): string
    {
        $requestParams = [];

        foreach ($page->params as $param => $value) {
            $requestParams[] = "$param: null";
        }

        $requestObject = 'const request = () => ({ ' . implode(', ', $requestParams) . ' });';

        return $requestObject . "\n" . $js;
    }

    private function extractVariableContext(ParsedPage $page): array
    {
        $context = [];

        foreach ($page->variables as $variable) {
            if (preg_match('/^[A-Za-z_][\w]*$/', $variable->name)) {
                $context[$variable->name] = $variable->value;
            }
        }

        return $context;
    }

    private function evaluatePhpExpression(string $expression, array $context): mixed
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ink');

        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create temporary file for expression evaluation.');
        }

        $code = "<?php\nreturn (function() {\n";

        foreach ($context as $name => $value) {
            $code .= sprintf('$%s = %s;' . "\n", $name, var_export($value, true));
        }

        $code .= sprintf("return %s;\n", $expression);
        $code .= "})();";

        try {
            if (file_put_contents($tempFile, $code) === false) {
                throw new \RuntimeException('Failed to write temporary evaluation file.');
            }

            $result = include $tempFile;
        } finally {
            @unlink($tempFile);
        }

        return $result;
    }

    /**
     * Get page variables for use in templates
     * 
     * @param ParsedPage $page
     * @return array<string, PageVariable>
     */
    public function getPageVariables(ParsedPage $page): array
    {
        return $page->variables;
    }

    /**
     * Minify HTML to reduce file size
     */
    public function minifyHtml(string $html): string
    {
        // Preserve and minify content inside <pre>, <textarea>, <script>, and <style> tags
        $preserveTags = [];
        $preserveIndex = 0;
        
        // Store <pre> content (no minification)
        $html = preg_replace_callback(
            '/<pre\b[^>]*>.*?<\/pre>/is',
            function ($matches) use (&$preserveTags, &$preserveIndex): string {
                $placeholder = "___PRESERVE_PRE_{$preserveIndex}___";
                $preserveTags[$placeholder] = $matches[0];
                $preserveIndex++;
                return $placeholder;
            },
            $html
        );
        
        // Store <textarea> content (no minification)
        $html = preg_replace_callback(
            '/<textarea\b[^>]*>.*?<\/textarea>/is',
            function ($matches) use (&$preserveTags, &$preserveIndex): string {
                $placeholder = "___PRESERVE_TEXTAREA_{$preserveIndex}___";
                $preserveTags[$placeholder] = $matches[0];
                $preserveIndex++;
                return $placeholder;
            },
            $html
        );
        
        // Store and minify <script> content
        $html = preg_replace_callback(
            '/<script\b([^>]*)>(.*?)<\/script>/is',
            function ($matches) use (&$preserveTags, &$preserveIndex): string {
                $placeholder = "___PRESERVE_SCRIPT_{$preserveIndex}___";
                $attributes = $matches[1];
                $content = $matches[2];
                
                // Only minify if it's JavaScript (not JSON or other types)
                if (stripos($attributes, 'type=') === false || stripos($attributes, 'javascript') !== false) {
                    $content = $this->minifyJs($content);
                }
                
                $preserveTags[$placeholder] = "<script{$attributes}>{$content}</script>";
                $preserveIndex++;
                return $placeholder;
            },
            $html
        );
        
        // Store and minify <style> content
        $html = preg_replace_callback(
            '/<style\b([^>]*)>(.*?)<\/style>/is',
            function ($matches) use (&$preserveTags, &$preserveIndex): string {
                $placeholder = "___PRESERVE_STYLE_{$preserveIndex}___";
                $attributes = $matches[1];
                $content = $this->minifyCss($matches[2]);
                
                $preserveTags[$placeholder] = "<style{$attributes}>{$content}</style>";
                $preserveIndex++;
                return $placeholder;
            },
            $html
        );
        
        // Remove HTML comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\[if\s)(?!<!)[^\[>].*?-->/s', '', $html);
        
        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Remove leading/trailing whitespace on each line
        $html = preg_replace('/^\s+|\s+$/m', '', $html);
        
        // Replace multiple spaces with single space
        $html = preg_replace('/\s{2,}/', ' ', $html);
        
        // Remove empty lines
        $html = preg_replace('/\n\s*\n/', "\n", $html);
        
        // Restore preserved content
        foreach ($preserveTags as $placeholder => $content) {
            $html = str_replace($placeholder, $content, $html);
        }
        
        return trim($html);
    }

    /**
     * Safely minify JavaScript code
     */
    private function minifyJs(string $js): string
    {
        // Remove single-line comments (but preserve URLs like http://)
        $js = preg_replace('#(?<!:)//[^\n]*#', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('#/\*.*?\*/#s', '', $js);
        
        // Remove leading/trailing whitespace on each line
        $js = preg_replace('/^\s+|\s+$/m', '', $js);
        
        // Replace multiple spaces with single space (but preserve strings)
        $js = preg_replace('/\s{2,}/', ' ', $js);
        
        // Remove spaces around operators (safe ones)
        $js = preg_replace('/\s*([=+\-*\/<>!&|,;:{}()\[\]])\s*/', '$1', $js);
        
        // Remove empty lines
        $js = preg_replace('/\n\s*\n/', "\n", $js);
        
        // Remove line breaks (but keep semicolons)
        $js = preg_replace('/\n/', '', $js);
        
        return trim($js);
    }

    /**
     * Safely minify CSS code
     */
    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        
        // Remove leading/trailing whitespace
        $css = preg_replace('/^\s+|\s+$/m', '', $css);
        
        // Remove spaces around special characters
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Replace multiple spaces with single space
        $css = preg_replace('/\s{2,}/', ' ', $css);
        
        // Remove empty lines
        $css = preg_replace('/\n\s*\n/', '', $css);
        
        // Remove line breaks
        $css = preg_replace('/\n/', '', $css);
        
        // Remove last semicolon in a block
        $css = preg_replace('/;}/','}',$css);
        
        return trim($css);
    }
}
