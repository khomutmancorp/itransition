<?php

declare(strict_types=1);

namespace App\DTO\ImportProducts;

readonly class ImportResult
{
    public function __construct(
        public int $processed,
        public int $success,
        public int $skipped,
        public array $errors
    ) {
    }

    final public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}