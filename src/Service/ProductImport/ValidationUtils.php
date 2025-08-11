<?php

declare(strict_types=1);

namespace App\Service\ProductImport;

class ValidationUtils
{
    public static function validateRequired(mixed $value, string $fieldName): string
    {
        $trimmed = trim((string) $value);
        
        if (empty($trimmed)) {
            throw new \InvalidArgumentException("{$fieldName} is required");
        }
        
        return $trimmed;
    }

    public static function validateOptionalInt(mixed $value, string $fieldName): ?int
    {
        if (empty($value)) {
            return null;
        }
        
        $trimmed = trim((string) $value);
        if (empty($trimmed)) {
            return null;
        }
        
        if (!is_numeric($trimmed)) {
            throw new \InvalidArgumentException("{$fieldName} must be a number");
        }
        
        $intValue = (int) $trimmed;
        if ($intValue < 0) {
            throw new \InvalidArgumentException("{$fieldName} cannot be negative");
        }
        
        return $intValue;
    }

    public static function validateOptionalFloat(mixed $value, string $fieldName): ?float
    {
        if (empty($value)) {
            return null;
        }
        
        $trimmed = trim((string) $value);
        if (empty($trimmed)) {
            return null;
        }
        
        if (!is_numeric($trimmed)) {
            throw new \InvalidArgumentException("{$fieldName} must be a number");
        }
        
        $floatValue = (float) $trimmed;
        if ($floatValue < 0) {
            throw new \InvalidArgumentException("{$fieldName} cannot be negative");
        }
        
        return $floatValue;
    }

    public static function parseDiscontinuedDate(mixed $value): ?\DateTimeInterface
    {
        if (empty($value)) {
            return null;
        }
        
        $trimmed = trim((string) $value);
        if (empty($trimmed) || strtolower($trimmed) === 'no' || strtolower($trimmed) === 'false') {
            return null;
        }
        
        if (strtolower($trimmed) === 'yes' || strtolower($trimmed) === 'true') {
            return new \DateTime();
        }
        
        // Try to parse as date
        try {
            return new \DateTime($trimmed);
        } catch (\Exception) {
            throw new \InvalidArgumentException("Invalid date format for discontinued field: {$trimmed}");
        }
    }
}