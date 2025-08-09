<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Batch;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\BulkWriterInterface;

final class BatchProcessor
{
    private array $batch = [];

    public function __construct(
        private readonly BulkWriterInterface $bulkWriter,
        private readonly int $batchSize = 1000
    ) {
    }

    public function addToBatch(ProductImportDTO $dto): void
    {
        $this->batch[] = $dto;
        
        if (count($this->batch) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if (empty($this->batch)) {
            return;
        }

        // Process all DTOs in current batch
        foreach ($this->batch as $dto) {
            $this->bulkWriter->addToBatch($dto);
        }
        
        // Flush to database
        $this->bulkWriter->flushBatch();
        
        // Clear memory
        $this->batch = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->batch);
    }
}