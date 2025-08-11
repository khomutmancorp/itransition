<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Statistics;

use App\DTO\ImportProducts\ImportResult;

class ImportStatistics
{
    private int $processed = 0;
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private array $errors = [];

    public function incrementProcessed(): void
    {
        $this->processed++;
    }

    public function incrementCreated(int $count = 1): void
    {
        $this->created += $count;
    }

    public function incrementUpdated(int $count = 1): void
    {
        $this->updated += $count;
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
            created: $this->created,
            updated: $this->updated,
            skipped: $this->skipped,
            errors: $this->errors
        );
    }
}