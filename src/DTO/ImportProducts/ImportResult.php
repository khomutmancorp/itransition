<?php

declare(strict_types=1);

namespace App\DTO\ImportProducts;

readonly class ImportResult
{
    public function __construct(
        public int $processed,
        public int $created,
        public int $updated,
        public int $skipped,
        public array $errors
    ) {
    }

    public function getSuccess(): int
    {
        return $this->created + $this->updated;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}