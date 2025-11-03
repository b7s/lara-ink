<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

final class Minifier
{
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
    public function minifyJs(string $js): string
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
    public function minifyCss(string $css): string
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
