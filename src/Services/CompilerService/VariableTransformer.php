<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

use B7s\LaraInk\DTOs\ParsedPage;

final class VariableTransformer
{
    public function transformVariables(string $html, ParsedPage $page): string
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

    public function replacePhpVariablesWithAlpine(string $html, ParsedPage $page): string
    {
        foreach ($page->params as $param => $value) {
            $html = str_replace('$' . $param, "lara_ink.request().$param", $html);
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

    /**
     * @return array<string, mixed>
     */
    public function extractVariableContext(ParsedPage $page): array
    {
        $context = [];

        foreach ($page->variables as $variable) {
            if (preg_match('/^[A-Za-z_][\w]*$/', $variable->name)) {
                $context[$variable->name] = $variable->value;
            }
        }

        return $context;
    }
}
