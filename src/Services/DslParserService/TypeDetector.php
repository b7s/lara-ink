<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\DslParserService;

final class TypeDetector
{
    /**
     * Detect the type of a variable
     * 
     * @param mixed $value
     * @return string
     */
    public function detectVariableType(mixed $value): string
    {
        if (is_string($value)) {
            return 'string';
        }
        
        if (is_int($value)) {
            return 'int';
        }
        
        if (is_float($value)) {
            return 'float';
        }
        
        if (is_bool($value)) {
            return 'bool';
        }
        
        if (is_array($value)) {
            return 'array';
        }
        
        if (is_object($value)) {
            $class = get_class($value);
            
            if (str_contains($class, 'Illuminate\\Support\\Collection')) {
                return 'collection';
            }
            
            if (str_contains($class, 'Illuminate\\Database\\Eloquent')) {
                return 'eloquent';
            }
            
            return 'object';
        }
        
        return 'unknown';
    }
}
