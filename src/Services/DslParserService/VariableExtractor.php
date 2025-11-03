<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\DslParserService;

use B7s\LaraInk\DTOs\PageVariable;

final class VariableExtractor
{
    public function __construct(
        private readonly TypeDetector $typeDetector,
    ) {}

    /**
     * Extract and process variables from PHP block
     * 
     * @param string $phpBlock
     * @param string $filePath
     * @return array<string, PageVariable>
     * @throws \RuntimeException
     */
    public function extractAndProcessVariables(string $phpBlock, string $filePath): array
    {
        $variables = [];
        
        // Remove PHP tags and ink_make() calls
        $phpBlock = preg_replace('/<\?php|\?>/', '', $phpBlock);
        $phpBlock = preg_replace('/ink_make\(\).*?;/s', '', $phpBlock);
        
        // Create a temporary file to execute and extract variables
        $tempFile = tempnam(sys_get_temp_dir(), 'lara_ink_');
        
        try {
            // Wrap code to capture variables
            $code = <<<'PHP'
<?php
return (function() {
    $__captured_vars = [];
    
    try {
PHP;
            $code .= $phpBlock;
            $code .= <<<'PHP'

        // Capture all defined variables
        foreach (get_defined_vars() as $name => $value) {
            if ($name !== '__captured_vars') {
                $__captured_vars[$name] = $value;
            }
        }
        
        return ['success' => true, 'vars' => $__captured_vars];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()];
    }
})();
PHP;
            
            file_put_contents($tempFile, $code);
            $result = include $tempFile;
            
            if (!$result['success']) {
                throw new \RuntimeException(
                    "Error processing variables in file: {$filePath}\n" .
                    "Line: {$result['line']}\n" .
                    "Error: {$result['error']}"
                );
            }
            
            foreach ($result['vars'] as $name => $value) {
                $variables[$name] = $this->createPageVariable($name, $value, $filePath);
            }
            
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        
        return $variables;
    }
    
    /**
     * Create a PageVariable with type detection and conversion
     * 
     * @param string $name
     * @param mixed $value
     * @param string $filePath
     * @return PageVariable
     * @throws \RuntimeException
     */
    public function createPageVariable(string $name, mixed $value, string $filePath): PageVariable
    {
        $type = $this->typeDetector->detectVariableType($value);
        $alpineVarName = 'var_' . $name . '_' . bin2hex(random_bytes(4));
        
        // Convert complex objects to arrays
        if (in_array($type, ['collection', 'eloquent', 'object'])) {
            if (method_exists($value, 'toArray')) {
                try {
                    $value = $value->toArray();
                    $type = 'array';
                } catch (\Throwable $e) {
                    throw new \RuntimeException(
                        "Error converting variable '\${$name}' to array in file: {$filePath}\n" .
                        "The object has a toArray() method but it failed with error: {$e->getMessage()}\n" .
                        "Possible issue: The object might have dependencies that are not available during build time."
                    );
                }
            } else {
                throw new \RuntimeException(
                    "Error processing variable '\${$name}' in file: {$filePath}\n" .
                    "Variable type: {$type}\n" .
                    "The variable is a complex object without a toArray() method.\n" .
                    "Please convert it to a simple array, or implement toArray() method."
                );
            }
        }
        
        return new PageVariable(
            name: $name,
            value: $value,
            type: $type,
            alpineVarName: $alpineVarName,
        );
    }
}
