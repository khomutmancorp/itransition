<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Batch;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\BulkWriterInterface;
use App\Service\ProductImport\ProductBulkWriter;
use App\Service\ProductImport\Statistics\ImportStatistics;

class BatchProcessor
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

    public function flush(?ImportStatistics $statistics = null): void
    {
        if (empty($this->batch)) {
            return;
        }

        $createdBefore = $this->getCreatedCount();
        $updatedBefore = $this->getUpdatedCount();

        // Process all DTOs in current batch
        foreach ($this->batch as $dto) {
            $this->bulkWriter->addToBatch($dto);
        }
        
        // Flush to database
        $this->bulkWriter->flushBatch();
        
        // Update statistics if provided
        if ($statistics !== null) {
            $createdAfter = $this->getCreatedCount();
            $updatedAfter = $this->getUpdatedCount();
            
            $createdDiff = $createdAfter - $createdBefore;
            $updatedDiff = $updatedAfter - $updatedBefore;
            
            $statistics->incrementCreated($createdDiff);
            $statistics->incrementUpdated($updatedDiff);
        }
        
        // Clear memory
        $this->batch = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->batch);
    }

    public function getCreatedCount(): int
    {
        if ($this->bulkWriter instanceof ProductBulkWriter) {
            return $this->bulkWriter->getCreatedCount();
        }
        return 0;
    }

    public function getUpdatedCount(): int
    {
        if ($this->bulkWriter instanceof ProductBulkWriter) {
            return $this->bulkWriter->getUpdatedCount();
        }
        return 0;
    }
}