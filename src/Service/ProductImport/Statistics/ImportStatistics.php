<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Statistics;

use App\DTO\ImportProducts\ImportResult;

final class ImportStatistics
{
    private int $processed = 0;
    private int $success = 0;
    private int $skipped = 0;
    private array $errors = [];

    public function incrementProcessed(): void
    {
        $this->processed++;
    }

    public function incrementSuccess(): void
    {
        $this->success++;
    }

    public function incrementSkipped(): void
    {
        $this->skipped++;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function toResult(): ImportResult
    {
        return new ImportResult(
            processed: $this->processed,
            success: $this->success,
            skipped: $this->skipped,
            errors: $this->errors
        );
    }
}