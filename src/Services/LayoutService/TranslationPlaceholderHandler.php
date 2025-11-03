<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\LayoutService;

final class TranslationPlaceholderHandler
{
    private const TRANSLATION_PLACEHOLDER_PREFIX = '__LARA_INK_TRANS__';

    /**
     * @param array<int, string> $placeholders
     */
    public function extractTranslationPlaceholders(string $content, array &$placeholders): string
    {
        $patterns = [
            ['pattern' => '/\{\{\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{\{\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{\{\s*trans_choice\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,[^}]*\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{\{\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{!!\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/\{!!\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/\{!!\s*trans_choice\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,[^}]*\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/\{!!\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^)]*)?\)/', 'type' => 'text'],
        ];

        foreach ($patterns as $config) {
            $pattern = $config['pattern'];
            $type = $config['type'];

            $content = preg_replace_callback(
                $pattern,
                function (array $matches) use (&$placeholders, $type): string {
                    $key = $matches[1];
                    $token = $this->generateTranslationToken($key, $type, $placeholders);

                    return $token;
                },
                $content
            );
        }

        return $content;
    }

    /**
     * @param array<string, array{key: string, type: string}> $placeholders
     */
    public function restoreTranslationPlaceholders(string $content, array $placeholders): string
    {
        foreach ($placeholders as $token => $data) {
            $key = $data['key'];
            $type = $data['type'];
            $escapedKey = str_replace("'", "\\'", $key);
            $fallback = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $replacement = $type === 'html'
                ? sprintf('<span x-html="lara_ink.trans(\'%s\')">%s</span>', $escapedKey, $fallback)
                : sprintf('<span x-text="lara_ink.trans(\'%s\')">%s</span>', $escapedKey, $fallback);

            $content = str_replace($token, $replacement, $content);
        }

        return $content;
    }

    /**
     * @param array<string, array{key: string, type: string}> $placeholders
     */
    private function generateTranslationToken(string $key, string $type, array &$placeholders): string
    {
        $token = self::TRANSLATION_PLACEHOLDER_PREFIX . str_replace('.', '_', uniqid('', true)) . '__';
        $placeholders[$token] = [
            'key' => $key,
            'type' => $type,
        ];

        return $token;
    }
}
